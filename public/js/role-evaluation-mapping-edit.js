/**
 * Role Evaluation Mapping Edit Page JavaScript
 * Handles loading and displaying criteria for editing existing mappings
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeEditPage();
});

/**
 * Initialize edit page functionality
 */
function initializeEditPage() {
    const roleToEvaluateSelect = document.getElementById('role_to_evaluate_id');
    
    if (roleToEvaluateSelect) {
        // Load criteria when role changes
        roleToEvaluateSelect.addEventListener('change', function() {
            loadCriteriaForRole(this.value);
        });
        
        // Load criteria on page load if role is already selected
        if (roleToEvaluateSelect.value) {
            loadCriteriaForRole(roleToEvaluateSelect.value);
        }
    }
}

/**
 * Load criteria for the selected role
 */
function loadCriteriaForRole(roleId) {
    const criteriaContainer = document.getElementById('criteriaContainer');
    
    if (!criteriaContainer) return;
    
    if (!roleId) {
        criteriaContainer.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                اختر الدور المراد تقييمه لعرض البنود المتاحة
            </div>
        `;
        return;
    }
    
    // Show loading state
    criteriaContainer.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
            <p class="mt-2 text-muted">جاري تحميل البنود...</p>
        </div>
    `;
    
    // Fetch criteria via AJAX
    fetch(`/role-evaluation-mapping/ajax/criteria-by-role?role_id=${roleId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.criteria) {
                renderCriteria(data.criteria);
            } else {
                showNoCriteriaMessage();
            }
        })
        .catch(error => {
            console.error('Error loading criteria:', error);
            showErrorMessage();
        });
}

/**
 * Render criteria checkboxes grouped by type
 */
function renderCriteria(criteriaGroups) {
    const criteriaContainer = document.getElementById('criteriaContainer');
    
    if (!criteriaContainer) return;
    
    let html = '<div class="criteria-groups">';
    
    // Check if we have any criteria
    const hasPositive = criteriaGroups.positive && criteriaGroups.positive.length > 0;
    const hasNegative = criteriaGroups.negative && criteriaGroups.negative.length > 0;
    
    if (!hasPositive && !hasNegative) {
        showNoCriteriaMessage();
        return;
    }
    
    // Render positive criteria
    if (hasPositive) {
        html += renderCriteriaGroup('positive', 'البنود الإيجابية', criteriaGroups.positive);
    }
    
    // Render negative criteria
    if (hasNegative) {
        html += renderCriteriaGroup('negative', 'البنود السلبية', criteriaGroups.negative);
    }
    
    html += '</div>';
    
    // Add select all buttons
    html = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllCriteria(true)">
                    <i class="fas fa-check-square"></i> تحديد الكل
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="selectAllCriteria(false)">
                    <i class="fas fa-square"></i> إلغاء التحديد
                </button>
            </div>
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                اختر البنود التي يمكن لهذا الدور تقييمها
            </small>
        </div>
    ` + html;
    
    criteriaContainer.innerHTML = html;
    
    // Pre-select linked criteria if available
    preselectLinkedCriteria();
}

/**
 * Render a single criteria group (positive or negative)
 */
function renderCriteriaGroup(type, title, criteria) {
    const typeColor = type === 'positive' ? 'success' : 'warning';
    const typeIcon = type === 'positive' ? 'plus-circle' : 'minus-circle';
    
    let html = `
        <div class="criteria-group mb-4">
            <h6 class="group-title text-${typeColor} mb-3">
                <i class="fas fa-${typeIcon}"></i>
                ${title}
                <span class="badge bg-${typeColor} ms-2">${criteria.length}</span>
            </h6>
            <div class="criteria-items">
    `;
    
    criteria.forEach(criterion => {
        html += `
            <div class="form-check criteria-item mb-2 p-2 border rounded">
                <input type="checkbox" 
                       name="criteria_ids[]" 
                       value="${criterion.id}"
                       id="criteria_${criterion.id}"
                       class="form-check-input criteria-checkbox"
                       data-type="${type}">
                <label for="criteria_${criterion.id}" class="form-check-label w-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <strong class="d-block">${criterion.criteria_name}</strong>
                            ${criterion.criteria_description ? 
                                `<small class="text-muted d-block mt-1">${criterion.criteria_description}</small>` 
                                : ''}
                            ${criterion.category ? 
                                `<span class="badge bg-secondary mt-1">${criterion.category}</span>` 
                                : ''}
                        </div>
                        <div class="text-end">
                            <span class="badge bg-info">${criterion.max_points} نقطة</span>
                        </div>
                    </div>
                </label>
            </div>
        `;
    });
    
    html += `
            </div>
        </div>
    `;
    
    return html;
}

/**
 * Pre-select criteria that are already linked
 */
function preselectLinkedCriteria() {
    if (window.linkedCriteriaIds && window.linkedCriteriaIds.length > 0) {
        window.linkedCriteriaIds.forEach(criteriaId => {
            const checkbox = document.getElementById(`criteria_${criteriaId}`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
        
        // Update selection count
        updateSelectionCount();
    }
}

/**
 * Select or deselect all criteria
 */
window.selectAllCriteria = function(select) {
    const checkboxes = document.querySelectorAll('.criteria-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = select;
    });
    
    updateSelectionCount();
}

/**
 * Update the count of selected criteria
 */
function updateSelectionCount() {
    const selectedCount = document.querySelectorAll('.criteria-checkbox:checked').length;
    const totalCount = document.querySelectorAll('.criteria-checkbox').length;
    
    // You can add a display element for this count if needed
    console.log(`Selected ${selectedCount} of ${totalCount} criteria`);
}

/**
 * Show message when no criteria available
 */
function showNoCriteriaMessage() {
    const criteriaContainer = document.getElementById('criteriaContainer');
    if (criteriaContainer) {
        criteriaContainer.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>لا توجد بنود متاحة لهذا الدور</strong>
                <p class="mb-0 mt-2 small">
                    يرجى إضافة بنود تقييم لهذا الدور أولاً من قائمة بنود التقييم
                </p>
            </div>
        `;
    }
}

/**
 * Show error message
 */
function showErrorMessage() {
    const criteriaContainer = document.getElementById('criteriaContainer');
    if (criteriaContainer) {
        criteriaContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <strong>حدث خطأ أثناء تحميل البنود</strong>
                <p class="mb-0 mt-2 small">
                    يرجى تحديث الصفحة والمحاولة مرة أخرى
                </p>
                <button type="button" class="btn btn-sm btn-danger mt-2" onclick="location.reload()">
                    <i class="fas fa-sync"></i> تحديث الصفحة
                </button>
            </div>
        `;
    }
}

/**
 * Add listener to checkboxes for counting
 */
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('criteria-checkbox')) {
        updateSelectionCount();
    }
});

