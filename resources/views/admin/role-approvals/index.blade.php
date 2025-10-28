@extends('layouts.app')

@section('title', 'إدارة قواعد اعتماد الأدوار')

@push('styles')
<style>
    .approval-type-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }

    .approval-type-administrative {
        background-color: #e3f2fd;
        color: #1565c0;
        border: 1px solid #bbdefb;
    }

    .approval-type-technical {
        background-color: #f3e5f5;
        color: #7b1fa2;
        border: 1px solid #ce93d8;
    }

    .table-responsive {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .table td {
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .approval-form {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .approval-matrix {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">إدارة قواعد اعتماد الأدوار</h1>
            <p class="text-muted mb-0">تحديد من يعتمد لمن في تسليمات المشاريع</p>
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createApprovalModal">
            <i class="fas fa-plus"></i>
            إضافة قاعدة جديدة
        </button>
    </div>

    <!-- Add New Approval Rule Form -->
    <div class="approval-form">
        <h5 class="mb-3">
            <i class="fas fa-plus-circle text-success"></i>
            إضافة قاعدة اعتماد جديدة
        </h5>

        <form id="createApprovalForm">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">الرول الذي يحتاج اعتماد</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">اختر الرول</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">الرول المعتمد</label>
                    <select name="approver_role_id" class="form-select" required>
                        <option value="">اختر المعتمد</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">نوع الاعتماد</label>
                    <select name="approval_type" class="form-select" required>
                        <option value="">اختر النوع</option>
                        <option value="administrative">إداري</option>
                        <option value="technical">فني</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">الوصف</label>
                    <input type="text" name="description" class="form-control" placeholder="وصف اختياري">
                </div>

                <div class="col-md-2">
                    <div class="form-check mt-4 pt-2">
                        <input type="checkbox" name="requires_same_project" class="form-check-input" id="requiresSameProject" value="1">
                        <label class="form-check-label small" for="requiresSameProject">
                            يجب أن يكون في نفس المشروع
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="requires_team_owner" class="form-check-input" id="requiresTeamOwner" value="1">
                        <label class="form-check-label small" for="requiresTeamOwner">
                            يجب أن يكون مالك الفريق
                        </label>
                    </div>
                </div>

                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-success d-block">
                        <i class="fas fa-save"></i>
                        إضافة
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Current Approval Rules -->
    <div class="approval-matrix">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="fas fa-list-alt text-primary"></i>
                قواعد الاعتماد الحالية
            </h5>
            <div>
                <span class="badge bg-info me-2">إجمالي: {{ $roleApprovals->count() }}</span>
            </div>
        </div>

        @if($roleApprovals->count() > 0)
            @foreach($approvalsByDepartment as $departmentName => $departmentApprovals)
                <div class="mb-4">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-building"></i>
                        {{ $departmentName }}
                        <span class="badge bg-secondary ms-2">{{ count($departmentApprovals) }}</span>
                    </h6>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
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
                                            <strong class="text-primary">{{ $approval->role->name }}</strong>
                                        </td>
                                        <td>
                                            <strong class="text-success">{{ $approval->approverRole->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge approval-type-badge approval-type-{{ $approval->approval_type }}">
                                                {{ $approval->approval_type == 'administrative' ? 'إداري' : 'فني' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($approval->description)
                                                <small class="text-muted">{{ $approval->description }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($approval->requires_same_project)
                                                <span class="badge bg-warning text-dark mb-1">
                                                    <i class="fas fa-project-diagram"></i>
                                                    نفس المشروع
                                                </span>
                                            @endif
                                            @if($approval->requires_team_owner)
                                                <span class="badge bg-info text-white">
                                                    <i class="fas fa-crown"></i>
                                                    مالك الفريق
                                                </span>
                                            @endif
                                            @if(!$approval->requires_same_project && !$approval->requires_team_owner)
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $approval->is_active ? 'success' : 'secondary' }}">
                                                {{ $approval->is_active ? 'نشط' : 'معطل' }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $approval->created_at->diffForHumans() }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary btn-sm edit-approval"
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
                                                <button class="btn btn-outline-{{ $approval->is_active ? 'warning' : 'success' }} btn-sm toggle-status"
                                                        data-id="{{ $approval->id }}"
                                                        title="{{ $approval->is_active ? 'إيقاف' : 'تفعيل' }}">
                                                    <i class="fas fa-{{ $approval->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm delete-approval"
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
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">لا توجد قواعد اعتماد محددة بعد</h5>
                <p class="text-muted">ابدأ بإضافة قاعدة اعتماد جديدة</p>
            </div>
        @endif
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل قاعدة الاعتماد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editApprovalForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="edit_approval_id" name="approval_id">

                    <div class="mb-3">
                        <label class="form-label">الرول الذي يحتاج اعتماد</label>
                        <select name="role_id" id="edit_role_id" class="form-select" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الرول المعتمد</label>
                        <select name="approver_role_id" id="edit_approver_role_id" class="form-select" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">نوع الاعتماد</label>
                        <select name="approval_type" id="edit_approval_type" class="form-select" required>
                            <option value="administrative">إداري</option>
                            <option value="technical">فني</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <input type="text" name="description" id="edit_description" class="form-control">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="requires_same_project" id="edit_requires_same_project" value="1">
                            <label class="form-check-label" for="edit_requires_same_project">
                                يجب أن يكون في نفس المشروع
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="requires_team_owner" id="edit_requires_team_owner" value="1">
                            <label class="form-check-label" for="edit_requires_team_owner">
                                يجب أن يكون مالك الفريق
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                            <label class="form-check-label" for="edit_is_active">
                                نشط
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
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
