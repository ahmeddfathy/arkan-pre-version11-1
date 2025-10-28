@extends('layouts.app')

@section('title', 'تعديل أدوار القسم')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12">
            <!-- Header -->
            <div class="page-header">
                <div class="header-content">
                    <div class="header-text">
                        <h1 class="page-title">
                            <i class="fas fa-edit"></i>
                            تعديل أدوار القسم
                        </h1>
                        <p class="page-subtitle">إدارة جميع الأدوار المرتبطة بقسم {{ $departmentName }}</p>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('department-roles.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i>
                            العودة
                        </a>
                    </div>
                </div>
            </div>

            <!-- Department Info -->
            <div class="department-info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="info-content">
                        <h3>قسم {{ $departmentName }}</h3>
                        <p>عدد الأدوار المرتبطة: <strong>{{ $departmentRoles->count() }}</strong></p>
                    </div>
                </div>
            </div>

            <!-- Edit Form Card -->
            <div class="form-card">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="form-title">
                        <h3>إدارة أدوار القسم</h3>
                        <p>يمكنك تعديل أو إضافة أو حذف الأدوار المرتبطة بالقسم</p>
                    </div>
                </div>

                <div class="form-body">
                    <form id="edit-form" method="POST" action="{{ route('department-roles.update', $departmentName) }}">
                        @csrf
                        @method('PUT')

                        <!-- Existing Roles Section -->
                        @if($departmentRoles->count() > 0)
                        <div class="form-section">
                            <label class="form-label-modern">
                                <i class="fas fa-users-cog"></i>
                                الأدوار الحالية
                                <span class="label-hint">يمكنك حذف الأدوار - المستوى الهرمي تلقائي</span>
                            </label>

                            <div class="existing-roles-grid">
                                @foreach($departmentRoles as $index => $departmentRole)
                                <div class="existing-role-card" data-role-id="{{ $departmentRole->id }}">
                                    <div class="role-card-header">
                                        <div class="role-info">
                                            <div class="role-icon">
                                                <i class="fas fa-user-tag"></i>
                                            </div>
                                            <div class="role-details">
                                                <h4>{{ $departmentRole->role->name }}</h4>
                                                <p>مستوى هرمي: {{ $departmentRole->hierarchy_level }}</p>
                                            </div>
                                        </div>
                                        <div class="role-actions">
                                            <button type="button" class="btn-delete-role" title="حذف الدور">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Add New Roles Section -->
                        @if(isset($availableRoles) && $availableRoles->count() > 0)
                        <div class="form-section">
                            <label class="form-label-modern">
                                <i class="fas fa-plus-circle"></i>
                                إضافة أدوار جديدة
                                <span class="label-hint">اختر الأدوار المتاحة - المستوى الهرمي سيتم تطبيقه تلقائياً</span>
                            </label>

                            <div class="add-roles-section">
                                <button type="button" id="add-role-btn" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-plus"></i>
                                    إضافة دور جديد
                                </button>

                                <div id="new-roles-container">
                                    <!-- New roles will be added here -->
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="form-section">
                            <label class="form-label-modern">
                                <i class="fas fa-info-circle"></i>
                                إضافة أدوار جديدة
                            </label>
                            <div class="alert alert-info alert-modern">
                                <div class="alert-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="alert-content">
                                    <h5>لا توجد أدوار متاحة للإضافة</h5>
                                    <p>جميع الأدوار الموجودة في النظام مرتبطة بالفعل بهذا القسم، أو لا توجد أدوار أخرى متاحة.</p>
                                </div>
                            </div>
                        </div>
                        @endif

                                                    <!-- Summary Section -->
                        <div class="form-section" id="summary-section" style="display: none;">
                            <label class="form-label-modern">
                                <i class="fas fa-chart-line"></i>
                                ملخص التغييرات
                            </label>
                            <div class="summary-card">
                                <div class="summary-stats">
                                    <div class="stat-item">
                                        <span class="stat-value" id="updated-count">0</span>
                                        <span class="stat-label">محدث</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value" id="added-count">0</span>
                                        <span class="stat-label">مضاف</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value" id="deleted-count">0</span>
                                        <span class="stat-label">محذوف</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Error Display -->
                        @if($errors->any())
                            <div class="alert alert-danger alert-modern">
                                <div class="alert-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="alert-content">
                                    <h5>يرجى تصحيح الأخطاء التالية:</h5>
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-lg" onclick="window.history.back()">
                                <i class="fas fa-times"></i>
                                إلغاء
                            </button>
                            <button type="submit" class="btn btn-primary btn-lg" id="submit-btn" disabled>
                                <i class="fas fa-save"></i>
                                <span id="submit-text">حفظ التغييرات</span>
                            </button>
                        </div>

                        <!-- Hidden fields for deleted roles -->
                        <div id="deleted-roles-inputs"></div>
                    </form>
                </div>
            </div>

            <!-- Warning Card -->
            <div class="warning-card">
                <div class="warning-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>تنبيه مهم</span>
                </div>
                <div class="warning-content">
                    <p>تعديل هذا الربط قد يؤثر على الموظفين الذين لديهم هذا الدور في القسم الحالي. تأكد من صحة التعديل قبل الحفظ.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
// Available roles data from server
window.availableRoles = [];

@if(isset($availableRoles) && $availableRoles->count() > 0)
    @foreach($availableRoles as $role)
        window.availableRoles.push({
            id: {{ $role->id }},
            name: @json($role->name)
        });
    @endforeach
@endif

console.log('Available roles loaded:', window.availableRoles);
console.log('Total available roles:', window.availableRoles.length);
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('edit-form');
    const summarySection = document.getElementById('summary-section');
    const addRoleBtn = document.getElementById('add-role-btn');
    const newRolesContainer = document.getElementById('new-roles-container');
    const deletedRolesInputs = document.getElementById('deleted-roles-inputs');

    let newRoleCounter = 0;
    let deletedRoles = [];
    let hasChanges = false;

    // Get available roles from window object and ensure it's an array
    let availableRoles = window.availableRoles || [];

    // Convert to array if it's an object
    if (availableRoles && typeof availableRoles === 'object' && !Array.isArray(availableRoles)) {
        availableRoles = Object.values(availableRoles);
    }

    // Ensure it's always an array
    if (!Array.isArray(availableRoles)) {
        availableRoles = [];
    }

    console.log('Final available roles array:', availableRoles);

    // Function to check for changes and update submit button
    function checkForChanges() {
        const newRoleSelects = document.querySelectorAll('select[name^="new_roles"]');
        const hasNewRoles = newRoleSelects.length > 0;
        const hasDeletedRoles = deletedRoles.length > 0;

        hasChanges = hasNewRoles || hasDeletedRoles;

        // Update submit button state
        if (hasChanges) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-secondary');
            submitBtn.classList.add('btn-primary');
            document.getElementById('submit-text').textContent = 'حفظ التغييرات';
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-secondary');
            document.getElementById('submit-text').textContent = 'لا توجد تغييرات';
        }

        // Update summary
        updateSummary();
    }

    // Function to update summary section
    function updateSummary() {
        const updatedCount = 0; // No updates for existing roles
        const addedCount = document.querySelectorAll('select[name^="new_roles"]').length;
        const deletedCount = deletedRoles.length;

        document.getElementById('updated-count').textContent = updatedCount;
        document.getElementById('added-count').textContent = addedCount;
        document.getElementById('deleted-count').textContent = deletedCount;

        if (hasChanges) {
            summarySection.style.display = 'block';
        } else {
            summarySection.style.display = 'none';
        }
    }

    // No need to store original values since existing roles can't be updated

    // Handle delete role buttons
    document.querySelectorAll('.btn-delete-role').forEach(button => {
        button.addEventListener('click', function() {
            const roleCard = this.closest('.existing-role-card');
            const roleId = roleCard.getAttribute('data-role-id');
            const roleName = roleCard.querySelector('h4').textContent;

            if (confirm('هل أنت متأكد من حذف الدور "' + roleName + '"؟')) {
                // Add to deleted roles
                deletedRoles.push(roleId);

                // Add hidden input for deleted role
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'deleted_roles[]';
                hiddenInput.value = roleId;
                deletedRolesInputs.appendChild(hiddenInput);

                // Remove the card
                roleCard.remove();

                checkForChanges();
            }
        });
    });

        // Handle add new role button
    console.log('Add role button found:', addRoleBtn);
    console.log('Available roles:', availableRoles);

    if (!addRoleBtn) {
        console.log('Add role button not found - this means no available roles or button is hidden');
    }

    if (addRoleBtn) {
        addRoleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Add role button clicked');

            console.log('Checking available roles:', availableRoles, 'Length:', availableRoles.length);

            if (!availableRoles || availableRoles.length === 0) {
                alert('لا توجد أدوار متاحة للإضافة. يرجى التأكد من وجود أدوار غير مرتبطة بهذا القسم.');
                console.log('No available roles found');
                return;
            }

            const newRoleDiv = document.createElement('div');
            newRoleDiv.className = 'new-role-card';

            let optionsHtml = '<option value="">اختر الدور</option>';

            // Safely iterate through available roles
            if (Array.isArray(availableRoles) && availableRoles.length > 0) {
                availableRoles.forEach(role => {
                    if (role && role.id && role.name) {
                        optionsHtml += '<option value="' + role.id + '">' + role.name + '</option>';
                    }
                });
            } else {
                console.log('No available roles to display');
            }

            newRoleDiv.innerHTML =
                '<div class="role-card-header">' +
                    '<div class="role-info">' +
                        '<div class="role-icon">' +
                            '<i class="fas fa-plus"></i>' +
                        '</div>' +
                        '<div class="role-details">' +
                            '<h4>دور جديد</h4>' +
                        '</div>' +
                    '</div>' +
                    '<div class="role-actions">' +
                        '<button type="button" class="btn-remove-new-role" title="إزالة">' +
                            '<i class="fas fa-times"></i>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
                '<div class="role-card-body">' +
                    '<div class="row">' +
                        '<div class="col-md-8">' +
                            '<label class="input-label">الدور</label>' +
                            '<select name="new_roles[' + newRoleCounter + '][role_id]" class="form-control-modern" required>' +
                                optionsHtml +
                            '</select>' +
                        '</div>' +
                        '<div class="col-md-4">' +
                            '<label class="input-label">المستوى الهرمي</label>' +
                            '<div class="auto-level-display">' +
                                '<i class="fas fa-magic"></i>' +
                                '<span>تلقائي</span>' +
                            '</div>' +
                            '<small class="input-hint">سيتم تطبيق المستوى تلقائياً</small>' +
                        '</div>' +
                    '</div>' +
                '</div>';

                        console.log('New role div created:', newRoleDiv);
            console.log('New roles container:', newRolesContainer);

            newRolesContainer.appendChild(newRoleDiv);
            newRoleCounter++;

            console.log('Role added to container, counter:', newRoleCounter);

            // Add event listeners for the new role
            const roleSelect = newRoleDiv.querySelector('select');
            const removeBtn = newRoleDiv.querySelector('.btn-remove-new-role');

            console.log('Elements found:', {roleSelect, removeBtn});

            if (roleSelect) roleSelect.addEventListener('change', checkForChanges);

            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    console.log('Remove button clicked');
                    newRoleDiv.remove();
                    checkForChanges();
                });
            }

            checkForChanges();
        });
    }

    // Handle form submission
    form.addEventListener('submit', function(e) {
        if (!hasChanges) {
            e.preventDefault();
            alert('لا توجد تغييرات للحفظ');
            return;
        }

        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
        submitBtn.disabled = true;
    });

    // Initial check
    checkForChanges();
});
</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/department-roles.css') }}">
@endpush
