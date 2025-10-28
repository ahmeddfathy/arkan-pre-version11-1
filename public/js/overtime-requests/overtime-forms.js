// Form Validation and Handling for Overtime Requests
document.addEventListener('DOMContentLoaded', function() {
    // Form Validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Create Modal Handler
    const registrationTypeInputs = document.querySelectorAll('input[name="registration_type"]');
    const employeeSelectContainer = document.getElementById('employee_select_container');
    const employeeIdSelect = document.getElementById('employee_id');
    const userIdInput = document.querySelector('input[name="user_id"]');

    if (registrationTypeInputs) {
        registrationTypeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'other') {
                    employeeSelectContainer.classList.remove('d-none');
                    employeeIdSelect.required = true;
                    userIdInput.value = '';
                } else {
                    employeeSelectContainer.classList.add('d-none');
                    employeeIdSelect.required = false;
                    employeeIdSelect.value = '';
                    userIdInput.value = document.querySelector('meta[name="user-id"]').content;
                }
            });
        });
    }

    // عند تغيير الموظف المختار
    if (employeeIdSelect) {
        employeeIdSelect.addEventListener('change', function() {
            userIdInput.value = this.value;
        });
    }

    // Status Change Handler
    const statusInputs = document.querySelectorAll('input[name="status"]');
    const rejectionContainer = document.getElementById('rejection_reason_container');
    const rejectionInput = document.getElementById('rejection_reason');

    if (statusInputs) {
        statusInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'rejected') {
                    rejectionContainer.classList.remove('d-none');
                    rejectionInput.required = true;
                } else {
                    rejectionContainer.classList.add('d-none');
                    rejectionInput.required = false;
                }
            });
        });
    }

    // Character count for reason textarea
    const editReason = document.getElementById('edit_reason');
    if (editReason) {
        editReason.addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('reasonCharCount').textContent = charCount;
        });
    }

    // Form validation for time inputs
    const editOvertimeForm = document.getElementById('editOvertimeForm');
    if (editOvertimeForm) {
        editOvertimeForm.addEventListener('submit', function(event) {
            const startTime = document.getElementById('edit_start_time').value;
            const endTime = document.getElementById('edit_end_time').value;

            if (endTime <= startTime) {
                event.preventDefault();
                document.getElementById('edit_end_time').setCustomValidity('يجب أن يكون وقت النهاية بعد وقت البداية');
                document.getElementById('edit_end_time').reportValidity();
            } else {
                document.getElementById('edit_end_time').setCustomValidity('');
            }
        });

        // Reset validation on input
        document.getElementById('edit_end_time').addEventListener('input', function() {
            this.setCustomValidity('');
        });
    }

    // Modify Status Change Handler
    const modifyStatusInputs = document.querySelectorAll('#modifyResponseForm input[name="status"]');
    const modifyRejectionContainer = document.getElementById('modify_rejection_reason_container');
    const modifyRejectionInput = document.getElementById('modify_rejection_reason');

    if (modifyStatusInputs) {
        modifyStatusInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'rejected') {
                    modifyRejectionContainer.classList.remove('d-none');
                    modifyRejectionInput.required = true;
                } else {
                    modifyRejectionContainer.classList.add('d-none');
                    modifyRejectionInput.required = false;
                    modifyRejectionInput.value = '';
                }
            });
        });
    }
});

// Reset status function
function resetStatus(requestId, type) {
    if (confirm('هل أنت متأكد من إعادة تعيين هذا الرد؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/overtime-requests/${requestId}/reset-${type}-status`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;

        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}
