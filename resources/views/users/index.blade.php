@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush
@section('content')
<div class="container-fluid px-4">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('import_summary'))
    <div class="alert alert-warning mb-4 import-summary-alert">
        <div class="d-flex align-items-center">
            <div class="me-3 text-warning">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-1 text-dark">ملخص استيراد المستخدمين</h5>
                    <span class="badge bg-danger">{{ session('skipped_count') ?? 0 }} تم تخطيهم</span>
                </div>
                <div class="mt-2">
                    <div class="import-summary-content">
                        @php
                            $summaryText = session('import_summary');
                            $lines = explode("\n", $summaryText);
                            $filteredLines = [];

                            foreach ($lines as $line) {
                                if (str_contains($line, 'الموظف موجود مسبقاً') || str_contains($line, 'غير متوفر') ||
                                    str_contains($line, 'بيانات إلزامية مفقودة')) {
                                    $filteredLines[] = $line;
                                }
                            }
                        @endphp

                        @foreach($filteredLines as $line)
                            <p class="mb-1 text-danger"><i class="fas fa-times-circle me-1"></i> {{ $line }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="ms-3">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif

    <!-- Search Form -->
    <div class="card search-card mb-4">
        <div class="card-body">
            <form action="{{ route('users.index') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Employee Name</label>
                        <input type="text" class="form-control search-input" name="employee_name"
                            value="{{ request('employee_name') }}" placeholder="Search by name..." list="employees_list">
                        <datalist id="employees_list">
                            @foreach ($employees as $employee)
                            <option value="{{ $employee->name }}">
                                @endforeach
                        </datalist>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select class="form-select search-input" name="department">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->department }}" {{ request('department') == $dept->department ? 'selected' : '' }}>
                                {{ $dept->department }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select search-input" name="status">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="out" {{ request('status') == 'out' ? 'selected' : '' }}>Out</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">User Information <span class="badge bg-primary ms-2">{{ $totalUsers }} Users</span></h4>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-file-import"></i> Import Users
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Employee ID</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Roles</th>

                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->employee_id }}</td>
                            <td>{{ $user->department }}</td>
                            <td>{{ $user->phone_number }}</td>
                            <td>
                                <span class="badge bg-{{ $user->employee_status == 'active' ? 'success' : 'danger' }}">
                                    {{ $user->employee_status }}
                                </span>
                            </td>
                            <td>
                                @if($user->roles->count() > 0)
                                    @foreach($user->roles as $role)
                                    <span class="badge bg-info me-1">{{ $role->name }}</span>
                                    @endforeach
                                    @if($user->roles->count() > 1)
                                        <small class="text-muted">({{ $user->roles->count() }} أدوار)</small>
                                    @endif
                                @else
                                    <span class="text-muted">لا يوجد أدوار</span>
                                @endif
                            </td>

                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-primary"
                                        data-user-id="{{ $user->id }}"
                                        data-user-name="{{ $user->name }}"
                                        data-roles='{{ $user->roles ? $user->roles->pluck("name") : "[]" }}'
                                        data-effective-permissions='{{ json_encode($user->effective_permissions ?? []) }}'
                                        onclick="openRolesModal(this.dataset.userId, this.dataset.userName)">
                                        <i class="fas fa-user-shield"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning"
                                        data-user-id="{{ $user->id }}"
                                        onclick="resetToEmployee(this.dataset.userId)">
                                        <i class="fas fa-user-tie"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger"
                                        data-user-id="{{ $user->id }}"
                                        onclick="removeRoles(this.dataset.userId)">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No users found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $users->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

@if(session('import_summary'))
<div class="modal fade" id="importResultsModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">نتائج استيراد الموظفين</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert {{ session('skipped_count') > 0 ? 'alert-warning' : 'alert-success' }}">
                    @if(session('imported_count'))
                    <p><strong>تم استيراد {{ session('imported_count') }} موظفين بنجاح</strong></p>
                    @endif

                    @if(session('skipped_count'))
                    <p><strong>تم تخطي {{ session('skipped_count') }} سجلات</strong></p>
                    @endif
                </div>

                <pre class="modal-body-pre">{{ session('import_summary') }}</pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var importResultsModal = new bootstrap.Modal(document.getElementById('importResultsModal'));
    importResultsModal.show();
});
</script>
@endif

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">استيراد الموظفين</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('user.import') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <p><strong>تعليمات استيراد البيانات:</strong></p>
                        <ul>
                            <li>يجب أن يكون الملف بصيغة Excel (.xlsx) أو CSV</li>
                            <li>البيانات الإلزامية: الاسم، رقم الهاتف، الرقم الوطني</li>
                            <li>سيتم تخطي الموظفين المكررين (رقم الموظف أو الرقم الوطني أو البريد الإلكتروني)</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">اختر ملف Excel</label>
                        <input type="file" name="file" class="form-control" required accept=".xlsx,.csv">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> استيراد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Roles Modal -->
<div class="modal fade" id="rolesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إدارة الأدوار والصلاحيات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> ميزات النظام المتقدم:</h6>
                    <ul class="mb-0 small">
                        <li><strong>أدوار متعددة:</strong> يمكن تعيين أكثر من دور واحد للمستخدم</li>
                        <li><strong>صلاحيات مرمزة بالألوان:</strong>
                            <span class="badge bg-primary">أزرق</span> = من الأدوار،
                            <span class="badge bg-success">أخضر</span> = إضافية،
                            <span class="badge bg-danger">أحمر</span> = محظورة
                        </li>
                        <li><strong>تحكم دقيق:</strong> إمكانية حظر صلاحيات معينة من الأدوار أو إضافة صلاحيات إضافية</li>
                    </ul>
                </div>

                <form id="rolesForm">
                    <input type="hidden" id="userId">

                    <div class="mb-3">
                        <label class="form-label">الأدوار <small class="text-muted">(يمكن اختيار أكثر من دور)</small></label>
                        <div id="rolesContainer" class="border p-3 rounded roles-container-inline">
                            @foreach($roles as $role)
                            <div class="form-check">
                                <input class="form-check-input role-checkbox"
                                    type="checkbox"
                                    name="roles[]"
                                    value="{{ $role->name }}"
                                    id="role_{{ $role->name }}"
                                    onchange="updatePermissionsByRole()">
                                <label class="form-check-label" for="role_{{ $role->name }}">
                                    {{ $role->name }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الصلاحيات</label>
                        <div id="permissionsContainer" class="border p-3 rounded">
                            @foreach($permissions as $permission)
                            <div class="form-check">
                                <input class="form-check-input permission-checkbox"
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->name }}"
                                    id="perm_{{ $permission->name }}">
                                <label class="form-check-label" for="perm_{{ $permission->name }}">
                                    {{ $permission->name }}
                                    <div id="sources_{{ $permission->name }}" class="mt-1"></div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" onclick="saveRolesAndPermissions()">حفظ التغييرات</button>
            </div>
        </div>
    </div>
</div>

<script>
    function removeRoles(userId) {
        if (confirm('هل أنت متأكد من إزالة جميع الأدوار والصلاحيات؟')) {
            $.ajax({
                url: `/users/${userId}/remove-roles`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    }
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء إزالة الأدوار والصلاحيات');
                }
            });
        }
    }

    function resetToEmployee(userId) {
        if (confirm('هل أنت متأكد من إعادة تعيين المستخدم كموظف؟')) {
            $.ajax({
                url: `/users/${userId}/reset-to-employee`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    }
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء إعادة التعيين');
                }
            });
        }
    }

    function openRolesModal(userId, userName) {
        $('#userId').val(userId);
        $('#rolesModal').modal('show');

        try {
            const $button = $(`button[data-user-id="${userId}"][data-user-name="${userName}"]`);
            const userRoles = JSON.parse($button.attr('data-roles') || '[]');

            // إعادة تعيين جميع checkboxes للأدوار
            $('.role-checkbox').prop('checked', false);

            // تحديد الأدوار الحالية
            userRoles.forEach(role => {
                $(`#role_${role}`).prop('checked', true);
            });

            // تحديث الصلاحيات بناءً على الأدوار المحددة
            updatePermissionsByRole();
        } catch (error) {
            console.error('Error parsing roles:', error);
            toastr.error('حدث خطأ في تحميل البيانات');
        }
    }

    function updatePermissionsByRole() {
        const selectedRoles = $('.role-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        const userId = $('#userId').val();

        // إعادة تعيين الصلاحيات والشارات
        $('.permission-checkbox').prop('checked', false);
        $('[id^="sources_"]').empty();

        if (selectedRoles.length === 0) {
            return;
        }

        // جلب صلاحيات جميع الأدوار المحددة
        const rolePermissionsPromises = selectedRoles.map(role => {
            return $.ajax({
                url: `/roles/${role}/permissions`,
                method: 'GET'
            }).then(permissions => ({ role, permissions }));
        });

        Promise.all(rolePermissionsPromises).then(rolesData => {
            console.log('All roles permissions:', rolesData);

            // تجميع جميع صلاحيات الأدوار
            const allRolePermissions = new Set();
            const permissionSources = {};

            rolesData.forEach(({ role, permissions }) => {
                permissions.forEach(permission => {
                    allRolePermissions.add(permission);
                    if (!permissionSources[permission]) {
                        permissionSources[permission] = [];
                    }
                    permissionSources[permission].push(role);
                });
            });

            // تحديد صلاحيات الأدوار وإضافة الشارات
            allRolePermissions.forEach(permission => {
                $(`#perm_${permission}`).prop('checked', true);
                const sources = permissionSources[permission];
                const badgeHtml = sources.map(role =>
                    `<span class="badge bg-primary me-1" title="من الدور: ${role}">${role}</span>`
                ).join('');
                $(`#sources_${permission}`).append(badgeHtml);
            });

            // جلب الصلاحيات الإضافية للمستخدم
            $.get(`/users/${userId}/additional-permissions`, function(additionalPermissions) {
                console.log('Additional permissions:', additionalPermissions);

                // تحديد الصلاحيات الإضافية وإضافة الشارة المناسبة
                additionalPermissions.forEach(permission => {
                    $(`#perm_${permission}`).prop('checked', true);
                    $(`#sources_${permission}`).append('<span class="badge bg-success me-1" title="صلاحية إضافية">إضافية</span>');
                });

                // جلب الصلاحيات المحظورة للمستخدم
                $.get(`/users/${userId}/forbidden-permissions`, function(forbiddenPermissions) {
                    console.log('Forbidden permissions:', forbiddenPermissions);

                    // إلغاء تحديد الصلاحيات المحظورة وإضافة شارة التحذير
                    forbiddenPermissions.forEach(permission => {
                        $(`#perm_${permission}`).prop('checked', false);
                        $(`#sources_${permission}`).append('<span class="badge bg-danger me-1" title="صلاحية محظورة">محظورة</span>');
                    });
                });
            });
        }).catch(error => {
            console.error('Error loading roles permissions:', error);
            toastr.error('حدث خطأ في تحميل صلاحيات الأدوار');
        });
    }

    function saveRolesAndPermissions() {
        const userId = $('#userId').val();
        const selectedRoles = $('.role-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        const selectedPermissions = $('.permission-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        const data = {
            _token: '{{ csrf_token() }}',
            permissions: selectedPermissions,
            roles: selectedRoles
        };

        $.ajax({
            url: `/users/${userId}/roles-permissions`,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#rolesModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء حفظ التغييرات';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
                console.error('Error details:', xhr.responseJSON);
            }
        });
    }
</script>@endsection
