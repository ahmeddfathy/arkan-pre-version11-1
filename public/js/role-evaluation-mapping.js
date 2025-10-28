/**
 * Role Evaluation Mapping JavaScript
 * Clean & Professional Functions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize preview functionality if elements exist
    initializePreview();
    // Initialize multiple evaluators functionality
    initializeMultipleEvaluators();
});

/**
 * Update preview content based on form selections
 */
window.updatePreview = function() {
    const previewContent = document.getElementById('preview-content');
    if (!previewContent) return;

    const departmentSelect = document.getElementById('department_name');
    const roleToEvaluateSelect = document.getElementById('role_to_evaluate_id');

    const department = departmentSelect?.value || '';
    const roleToEvaluate = roleToEvaluateSelect?.options[roleToEvaluateSelect.selectedIndex]?.text || '';
    const evaluators = getEvaluatorsSummary();

    if (department && roleToEvaluate) {
        previewContent.innerHTML = `
            <div class="row">
                <div class="col-md-12">
                    <h6 class="text-primary">تفاصيل الربط:</h6>
                    <ul class="list-unstyled">
                        <li><strong>القسم:</strong> ${department}</li>
                        <li><strong>الدور المُراد تقييمه:</strong> ${roleToEvaluate}</li>
                        <li><strong>عدد المقيمين:</strong> ${evaluators.count}</li>
                        ${evaluators.list ? `<li><strong>المقيمين:</strong><br>${evaluators.list}</li>` : ''}
                    </ul>
                </div>
            </div>
        `;
    } else {
        previewContent.innerHTML = '<span class="text-muted">اختر القسم والدور المراد تقييمه، ثم أضف المقيمين</span>';
    }
}

/**
 * Initialize preview functionality for create/edit forms
 */
function initializePreview() {
    const departmentSelect = document.getElementById('department_name');
    const roleToEvaluateSelect = document.getElementById('role_to_evaluate_id');
    const previewContent = document.getElementById('preview-content');

    // Check if elements exist
    if (departmentSelect && roleToEvaluateSelect && previewContent) {
        // Bind events to form elements
        departmentSelect.addEventListener('change', function() {
            loadRolesByDepartment(this.value);
            updatePreview();
        });
        roleToEvaluateSelect.addEventListener('change', updatePreview);

        // Initial preview update
        updatePreview();
    }
}

/**
 * Smooth scroll to element
 */
function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

/**
 * Show loading state on buttons
 */
function setButtonLoading(buttonElement, loading = true) {
    if (!buttonElement) return;

    if (loading) {
        buttonElement.disabled = true;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...';
    } else {
        buttonElement.disabled = false;
        // Restore original content - this would need to be stored beforehand
    }
}

/**
 * Initialize tooltips if Bootstrap is available
 */
function initializeTooltips() {
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * Initialize confirmation dialogs
 */
function initializeConfirmations() {
    const deleteButtons = document.querySelectorAll('button[type="submit"][title="حذف"]');
    deleteButtons.forEach(button => {
        const form = button.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const confirmed = confirm('هل أنت متأكد من حذف هذا الربط؟');
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        }
    });
}

/**
 * Initialize multiple evaluators functionality
 */
function initializeMultipleEvaluators() {
    const addEvaluatorBtn = document.getElementById('addEvaluatorBtn');

    if (addEvaluatorBtn) {
        addEvaluatorBtn.addEventListener('click', addNewEvaluator);
    }
}

let evaluatorCounter = 0;

/**
 * Add new evaluator section
 */
function addNewEvaluator() {
    const roleToEvaluateSelect = document.getElementById('role_to_evaluate_id');

    if (!roleToEvaluateSelect.value) {
        alert('يرجى اختيار الدور المراد تقييمه أولاً');
        return;
    }

    evaluatorCounter++;
    const evaluatorsContainer = document.getElementById('evaluatorsContainer');
    const noEvaluatorsMessage = document.getElementById('noEvaluatorsMessage');

    const evaluatorHTML = createEvaluatorTemplate(evaluatorCounter);
    evaluatorsContainer.insertAdjacentHTML('beforeend', evaluatorHTML);

    // Hide no evaluators message
    if (noEvaluatorsMessage) {
        noEvaluatorsMessage.style.display = 'none';
    }

    // Initialize events for new evaluator
    initializeEvaluatorEvents(evaluatorCounter);

    // Load criteria for this evaluator
    loadCriteriaForEvaluator(evaluatorCounter, roleToEvaluateSelect.value);

    // Update preview
    updatePreview();
}

/**
 * Create HTML template for evaluator
 */
function createEvaluatorTemplate(index) {
    return `
        <div class="evaluator-card mb-3" id="evaluator-${index}">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-user-check text-success"></i>
                            مقيم رقم ${index}
                        </h6>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeEvaluator(${index})">
                            <i class="fas fa-times"></i> حذف
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Evaluator Role -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="evaluator_role_${index}" class="form-label">الدور المُقيم</label>
                                <select name="evaluators[${index}][role_id]"
                                        id="evaluator_role_${index}"
                                        class="form-select evaluator-role-select"
                                        required>
                                    <option value="">اختر الدور</option>
                                    ${getRoleOptions()}
                                </select>
                            </div>
                        </div>

                        <!-- Permissions -->
                        <div class="col-md-6">
                            <label class="form-label">الصلاحيات</label>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="evaluators[${index}][can_evaluate]"
                                           id="can_evaluate_${index}"
                                           class="form-check-input"
                                           value="1" checked>
                                    <label for="can_evaluate_${index}" class="form-check-label">
                                        يستطيع التقييم
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="evaluators[${index}][can_view]"
                                           id="can_view_${index}"
                                           class="form-check-input"
                                           value="1" checked>
                                    <label for="can_view_${index}" class="form-check-label">
                                        يستطيع المشاهدة
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Criteria Selection -->
                    <div class="mb-3">
                        <label class="form-label">البنود التي سيقيمها</label>
                        <div id="criteria-container-${index}" class="criteria-selection">
                            <div class="text-center py-3">
                                <i class="fas fa-spinner fa-spin"></i> جاري تحميل البنود...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Get role options HTML for evaluators (all roles, not filtered by department)
 */
function getRoleOptions() {
    let options = '';

    // Get all roles from the original page load (before filtering)
    if (window.allAvailableRoles && window.allAvailableRoles.length > 0) {
        window.allAvailableRoles.forEach(role => {
            options += `<option value="${role.id}">${role.display_name}</option>`;
        });
    } else {
        // This shouldn't happen, but fallback to current roles
        console.warn('All available roles not found, using filtered roles as fallback');
        if (window.availableRoles && window.availableRoles.length > 0) {
            window.availableRoles.forEach(role => {
                options += `<option value="${role.id}">${role.display_name}</option>`;
            });
        }
    }

    return options;
}

/**
 * Initialize events for evaluator
 */
function initializeEvaluatorEvents(index) {
    const evaluatorRoleSelect = document.getElementById(`evaluator_role_${index}`);
    const canEvaluateCheck = document.getElementById(`can_evaluate_${index}`);
    const canViewCheck = document.getElementById(`can_view_${index}`);

    if (evaluatorRoleSelect) {
        evaluatorRoleSelect.addEventListener('change', updatePreview);
    }
    if (canEvaluateCheck) {
        canEvaluateCheck.addEventListener('change', updatePreview);
    }
    if (canViewCheck) {
        canViewCheck.addEventListener('change', updatePreview);
    }
}

/**
 * Remove evaluator
 */
window.removeEvaluator = function(index) {
    const evaluatorCard = document.getElementById(`evaluator-${index}`);
    if (evaluatorCard) {
        evaluatorCard.remove();

        // Show no evaluators message if no evaluators left
        const evaluatorsContainer = document.getElementById('evaluatorsContainer');
        const noEvaluatorsMessage = document.getElementById('noEvaluatorsMessage');

        if (evaluatorsContainer && evaluatorsContainer.children.length === 0 && noEvaluatorsMessage) {
            noEvaluatorsMessage.style.display = 'block';
        }

        updatePreview();
    }
}

/**
 * Load criteria for specific evaluator
 */
function loadCriteriaForEvaluator(evaluatorIndex, roleId) {
    if (!roleId) return;

    const criteriaContainer = document.getElementById(`criteria-container-${evaluatorIndex}`);
    if (!criteriaContainer) return;

    // Show loading state
    criteriaContainer.innerHTML = `
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin"></i> جاري تحميل البنود...
        </div>
    `;

    // Make AJAX request
    fetch(`/role-evaluation-mapping/ajax/criteria-by-role?role_id=${roleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCriteriaForEvaluator(evaluatorIndex, data.criteria);
            } else {
                criteriaContainer.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        لا توجد بنود متاحة لهذا الدور
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading criteria:', error);
            criteriaContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    حدث خطأ أثناء تحميل البنود
                </div>
            `;
        });
}

/**
 * Render criteria for specific evaluator
 */
function renderCriteriaForEvaluator(evaluatorIndex, criteriaGroups) {
    const criteriaContainer = document.getElementById(`criteria-container-${evaluatorIndex}`);
    if (!criteriaContainer) return;

    let html = '<div class="criteria-groups">';

    Object.keys(criteriaGroups).forEach(type => {
        const criteria = criteriaGroups[type];
        const typeLabel = type === 'positive' ? 'البنود الإيجابية' : 'البنود السلبية';
        const typeColor = type === 'positive' ? 'success' : 'warning';

        html += `
            <div class="criteria-group mb-3">
                <h6 class="group-title text-${typeColor}">
                    <i class="fas fa-${type === 'positive' ? 'plus-circle' : 'minus-circle'}"></i>
                    ${typeLabel}
                </h6>
                <div class="criteria-items">
        `;

        criteria.forEach(criterion => {
            html += `
                <div class="form-check criteria-item">
                    <input type="checkbox"
                           name="evaluators[${evaluatorIndex}][criteria_ids][]"
                           value="${criterion.id}"
                           id="criteria_${evaluatorIndex}_${criterion.id}"
                           class="form-check-input">
                    <label for="criteria_${evaluatorIndex}_${criterion.id}" class="form-check-label">
                        <strong>${criterion.criteria_name}</strong>
                        <small class="text-muted d-block">النقاط: ${criterion.max_points}</small>
                        ${criterion.criteria_description ? `<small class="text-muted d-block">${criterion.criteria_description}</small>` : ''}
                        ${criterion.category ? `<span class="badge bg-secondary ms-2">${criterion.category}</span>` : ''}
                    </label>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    });

    html += '</div>';

    if (Object.keys(criteriaGroups).length === 0) {
        html = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                لا توجد بنود متاحة لهذا الدور
            </div>
        `;
    }

    criteriaContainer.innerHTML = html;
}

/**
 * Get evaluators summary for preview
 */
function getEvaluatorsSummary() {
    const evaluators = document.querySelectorAll('.evaluator-card');
    const count = evaluators.length;
    let list = '';

    evaluators.forEach((evaluator, index) => {
        const roleSelect = evaluator.querySelector('.evaluator-role-select');
        const canEvaluate = evaluator.querySelector('[name*="can_evaluate"]:checked');
        const canView = evaluator.querySelector('[name*="can_view"]:checked');

        if (roleSelect && roleSelect.value) {
            const roleName = roleSelect.options[roleSelect.selectedIndex].text;
            const permissions = [];
            if (canEvaluate) permissions.push('تقييم');
            if (canView) permissions.push('مشاهدة');

            list += `<small class="text-muted">• ${roleName} (${permissions.join(', ')})</small><br>`;
        }
    });

    return { count, list };
}

/**
 * Load roles by department via AJAX
 */
function loadRolesByDepartment(departmentName) {
    const roleToEvaluateSelect = document.getElementById('role_to_evaluate_id');

    if (!departmentName || !roleToEvaluateSelect) {
        return;
    }

    // Reset role select and show loading
    roleToEvaluateSelect.innerHTML = '<option value="">جاري التحميل...</option>';
    roleToEvaluateSelect.disabled = true;

    // Clear evaluators when department changes
    clearAllEvaluators();

    // Make AJAX request
    fetch(`/role-evaluation-mapping/ajax/roles-by-department?department_name=${encodeURIComponent(departmentName)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.roles.length > 0) {
                let options = '<option value="">اختر الدور</option>';
                data.roles.forEach(role => {
                    options += `<option value="${role.id}">${role.display_name}</option>`;
                });
                roleToEvaluateSelect.innerHTML = options;

                // Store roles globally for evaluator dropdowns
                window.availableRoles = data.roles;

            } else {
                roleToEvaluateSelect.innerHTML = '<option value="">لا توجد أدوار متاحة</option>';
                window.availableRoles = [];
            }
            roleToEvaluateSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading roles:', error);
            roleToEvaluateSelect.innerHTML = '<option value="">حدث خطأ في التحميل</option>';
            roleToEvaluateSelect.disabled = false;
        });
}

/**
 * Clear all evaluators when department changes
 */
function clearAllEvaluators() {
    const evaluatorsContainer = document.getElementById('evaluatorsContainer');
    const noEvaluatorsMessage = document.getElementById('noEvaluatorsMessage');

    if (evaluatorsContainer) {
        evaluatorsContainer.innerHTML = '';
        evaluatorCounter = 0;
    }

    if (noEvaluatorsMessage) {
        noEvaluatorsMessage.style.display = 'block';
    }

    updatePreview();
}

// Initialize additional functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeTooltips();
    initializeConfirmations();
});
