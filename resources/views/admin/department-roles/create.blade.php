@extends('layouts.app')

@section('title', 'ربط أدوار القسم')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <!-- Header -->
            <div class="page-header">
                <div class="header-content">
                    <div class="header-text">
                        <h1 class="page-title">
                            <i class="fas fa-link"></i>
                            ربط أدوار القسم
                        </h1>
                        <p class="page-subtitle">اختر القسم والأدوار المرتبطة به</p>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('department-roles.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i>
                            العودة
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Form Card -->
            <div class="form-card">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <div class="form-title">
                        <h3>ربط الأدوار بالقسم</h3>
                        <p>اختر القسم ثم حدد الأدوار المناسبة له</p>
                    </div>
                </div>

                <div class="form-body">
                    <form id="department-roles-form" method="POST">
                        @csrf

                        <!-- Department Selection -->
                        <div class="form-section">
                            <label class="form-label-modern">
                                <i class="fas fa-building"></i>
                                اختر القسم
                            </label>
                            <select class="form-control-modern @error('department_name') is-invalid @enderror"
                                    id="department_name"
                                    name="department_name"
                                    required>
                                <option value="">-- اختر القسم --</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department }}"
                                            {{ old('department_name', request('department')) == $department ? 'selected' : '' }}>
                                        {{ $department }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                                                <!-- Roles Selection -->
                        <div class="form-section" id="roles-section" style="display: none;">
                            <label class="form-label-modern">
                                <i class="fas fa-user-tag"></i>
                                اختر الأدوار المطلوبة
                                <span class="label-hint">المستوى الهرمي لكل دور سيتم تطبيقه تلقائياً من النظام</span>
                            </label>

                            <!-- Simple Roles Selection -->
                            <div class="roles-simple-grid">
                                @foreach($roles as $role)
                                    <div class="role-simple-card">
                                        <input type="checkbox"
                                               id="role_{{ $role->id }}"
                                               name="role_ids[]"
                                               value="{{ $role->id }}"
                                               class="role-checkbox">
                                        <label for="role_{{ $role->id }}" class="role-simple-label">
                                            <div class="role-icon">
                                                <i class="fas fa-user-tag"></i>
                                            </div>
                                            <div class="role-info">
                                                <h4>{{ $role->name }}</h4>
                                                <p>دور {{ $role->name }}</p>
                                            </div>
                                            <div class="role-check">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Auto Hierarchy Info -->
                            <div class="auto-hierarchy-info">
                                <div class="info-icon">
                                    <i class="fas fa-magic"></i>
                                </div>
                                <div class="info-text">
                                    <h5>تطبيق تلقائي للمستويات الهرمية</h5>
                                    <p>سيتم تطبيق المستوى الهرمي لكل دور تلقائياً حسب إعدادات النظام المحددة مسبقاً</p>
                                </div>
                            </div>
                        </div>

                        <!-- Selection Preview -->
                        <div class="form-section" id="preview-section" style="display: none;">
                            <label class="form-label-modern">
                                <i class="fas fa-eye"></i>
                                معاينة الاختيار
                            </label>
                            <div class="selection-preview" id="selection-preview-container">
                                <!-- Will be populated by JavaScript -->
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
                            <button type="submit" class="btn btn-success btn-lg" id="submit-btn" disabled>
                                <i class="fas fa-save"></i>
                                <span id="submit-text">حفظ الربط</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Card -->
            <div class="help-card">
                <div class="help-header">
                    <i class="fas fa-lightbulb"></i>
                    <span>نصائح مفيدة</span>
                </div>
                <div class="help-content">
                    <div class="help-items">
                        <div class="help-item">
                            <i class="fas fa-magic"></i>
                            <span>المستوى الهرمي لكل دور يتم تطبيقه تلقائياً من النظام</span>
                        </div>
                        <div class="help-item">
                            <i class="fas fa-cogs"></i>
                            <span>لإدارة المستويات الهرمية، استخدم صفحة "إدارة ترتيب الأدوار"</span>
                        </div>
                        <div class="help-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>لا يمكن ربط نفس الدور بنفس القسم أكثر من مرة</span>
                        </div>
                        <div class="help-item">
                            <i class="fas fa-info-circle"></i>
                            <span>فقط الأدوار التي لها مستوى هرمي محدد يمكن ربطها</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_name');
    const rolesSection = document.getElementById('roles-section');
    const previewSection = document.getElementById('preview-section');
    const roleCheckboxes = document.querySelectorAll('.role-checkbox');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const previewContainer = document.getElementById('selection-preview-container');
    const form = document.getElementById('department-roles-form');

    // Show roles section when department is selected
    departmentSelect.addEventListener('change', function() {
        if (this.value) {
            rolesSection.style.display = 'block';
            rolesSection.classList.add('fade-in');
        } else {
            rolesSection.style.display = 'none';
            previewSection.style.display = 'none';
            updateSubmitButton();
        }
    });

        // Handle role checkbox changes
    roleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const card = this.closest('.role-simple-card');

            if (this.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }

            updatePreview();
        });
    });

        function updatePreview() {
        const departmentSelected = departmentSelect.value;
        const selectedRoles = [];

        roleCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const roleId = checkbox.value;
                const roleName = checkbox.nextElementSibling.querySelector('h4').textContent;

                selectedRoles.push({
                    id: roleId,
                    name: roleName
                });
            }
        });

        if (selectedRoles.length > 0 && departmentSelected) {
            previewSection.style.display = 'block';
            previewSection.classList.add('fade-in');

            const departmentText = departmentSelect.options[departmentSelect.selectedIndex].text;

            let rolesHtml = selectedRoles.map(role => `
                <div class="preview-role-item">
                    <div class="preview-role-info">
                        <span class="role-name">${role.name}</span>
                        <span class="role-hierarchy">مستوى هرمي تلقائي</span>
                    </div>
                </div>
            `).join('');

            previewContainer.innerHTML = `
                <div class="selection-preview-item">
                    <div class="preview-header">
                        <i class="fas fa-building"></i>
                        <h4>معاينة الروابط (${selectedRoles.length})</h4>
                    </div>
                    <div class="preview-details">
                        <div class="preview-row">
                            <span class="preview-label">القسم:</span>
                            <span class="preview-value">${departmentText}</span>
                        </div>
                        <div class="preview-roles">
                            <span class="preview-label">الأدوار المختارة:</span>
                            <div class="selected-roles-list">
                                ${rolesHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            previewSection.style.display = 'none';
        }

        updateSubmitButton();
    }

    function updateSubmitButton() {
        const departmentSelected = departmentSelect.value;
        let validSelections = 0;

        roleCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                validSelections++;
            }
        });

        if (validSelections > 0 && departmentSelected) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-secondary');
            submitBtn.classList.add('btn-success');
            submitText.textContent = validSelections === 1 ? 'حفظ الربط' : `حفظ ${validSelections} روابط`;
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-secondary');
            submitText.textContent = 'اختر القسم والأدوار أولاً';
        }
    }

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const selectedRoles = Array.from(roleCheckboxes).filter(cb => cb.checked);
        const department = departmentSelect.value;

        if (!department || selectedRoles.length === 0) {
            alert('يرجى اختيار القسم والأدوار');
            return;
        }

        // Prepare role data (no hierarchy validation needed)
        const roleData = [];
        selectedRoles.forEach(checkbox => {
            const roleId = checkbox.value;
            roleData.push({
                role_id: roleId
            });
        });

        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
        submitBtn.disabled = true;

        // Prepare form data for single request
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        formData.append('department_name', department);

        // Add role IDs array
        roleData.forEach((role, index) => {
            formData.append(`role_ids[${index}]`, role.role_id);
        });

        console.log('Sending data:', {
            department_name: department,
            role_ids: roleData.map(r => r.role_id)
        });

        // Submit single request with all roles
        fetch('{{ route("department-roles.store") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);

            if (!response.ok) {
                return response.json().then(data => {
                    console.error('Error response data:', data);
                    let errorMessage = 'حدث خطأ أثناء الحفظ';

                    if (data.errors && Array.isArray(data.errors)) {
                        errorMessage = data.errors.join('\n');
                    } else if (data.message) {
                        errorMessage = data.message;
                    }

                    throw new Error(errorMessage);
                }).catch(jsonError => {
                    // If response is not JSON, throw generic error
                    throw new Error('حدث خطأ في الخادم. يرجى المحاولة مرة أخرى.');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Success response data:', data);

            if (data.success) {
                let message = data.message || 'تم حفظ الروابط بنجاح';

                // Show warnings if any
                if (data.warnings && data.warnings.length > 0) {
                    message += '\n\nتحذيرات:\n' + data.warnings.join('\n');
                }

                alert(message);
                // Redirect to index
                window.location.href = '{{ route("department-roles.index") }}';
            } else {
                let errorMessage = data.message || 'حدث خطأ أثناء الحفظ';

                if (data.errors && Array.isArray(data.errors)) {
                    errorMessage += '\n\nالأخطاء:\n' + data.errors.join('\n');
                }

                throw new Error(errorMessage);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'حدث خطأ أثناء الحفظ. يرجى المحاولة مرة أخرى.');

            // Reset button
            submitBtn.innerHTML = '<i class="fas fa-save"></i> حفظ الروابط';
            updateSubmitButton();
        });
    });

    // Initial state
    if (departmentSelect.value) {
        rolesSection.style.display = 'block';
    }
    updateSubmitButton();
});
</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/department-roles.css') }}">
@endpush
