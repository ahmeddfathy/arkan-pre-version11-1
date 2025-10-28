// Handle response status change in modals
function handleStatusChange(selectElement, reasonContainer) {
    if (selectElement.value === 'rejected') {
        reasonContainer.style.display = 'block';
        reasonContainer.querySelector('textarea').setAttribute('required', 'required');
    } else {
        reasonContainer.style.display = 'none';
        reasonContainer.querySelector('textarea').removeAttribute('required');
    }
}

// Initialize modals
document.addEventListener('DOMContentLoaded', function() {
    // Handle registration type change
    const registrationTypeInputs = document.querySelectorAll('input[name="registration_type"]');
    const employeeSelectContainer = document.getElementById('employee_select_container');
    const userIdSelect = document.getElementById('user_id');

    if (registrationTypeInputs && employeeSelectContainer) {
        registrationTypeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'other') {
                    employeeSelectContainer.style.display = 'block';
                    if (userIdSelect) {
                        userIdSelect.required = true;
                    }
                } else {
                    employeeSelectContainer.style.display = 'none';
                    if (userIdSelect) {
                        userIdSelect.required = false;
                        userIdSelect.value = '';
                    }
                }
            });
        });
    }

    // Handle response buttons (Manager & HR)
    document.querySelectorAll('.respond-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const responseType = this.dataset.responseType;
            const form = document.getElementById('respondForm');
            const responseTypeInput = document.getElementById('response_type');

            responseTypeInput.value = responseType;
            form.action = responseType === 'hr'
                ? `/permission-requests/${requestId}/hr-status`
                : `/permission-requests/${requestId}/manager-status`;
        });
    });

    // Handle edit buttons
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.id;
            const departureTime = this.dataset.departure;
            const returnTime = this.dataset.return;
            const reason = this.dataset.reason;

            const form = document.getElementById('editPermissionForm');
            form.action = `/permission-requests/${requestId}`;

            document.getElementById('edit_departure_time').value = departureTime.replace(' ', 'T');
            document.getElementById('edit_return_time').value = returnTime.replace(' ', 'T');
            document.getElementById('edit_reason').value = reason;
        });
    });

    // Handle modify response buttons
    document.querySelectorAll('.modify-response-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const responseType = this.dataset.responseType;
            const status = this.dataset.status;
            const reason = this.dataset.reason || '';

            const form = document.getElementById('modifyResponseForm');
            const responseTypeInput = document.getElementById('modify_response_type');
            const statusInput = document.getElementById('modify_status');
            const reasonInput = document.getElementById('modify_reason');

            responseTypeInput.value = responseType;
            statusInput.value = status;
            reasonInput.value = reason;

            form.action = `/permission-requests/${requestId}/${responseType === 'hr' ? 'modify-hr-status' : 'modify-manager-status'}`;

            const reasonContainer = document.getElementById('modify_reason_container');
            reasonContainer.style.display = status === 'rejected' ? 'block' : 'none';
        });
    });

    // Handle response status change
    document.getElementById('response_status')?.addEventListener('change', function() {
        const reasonContainer = document.getElementById('rejection_reason_container');
        reasonContainer.style.display = this.value === 'rejected' ? 'block' : 'none';
    });

    // Handle modify status change
    document.getElementById('modify_status')?.addEventListener('change', function() {
        const reasonContainer = document.getElementById('modify_reason_container');
        reasonContainer.style.display = this.value === 'rejected' ? 'block' : 'none';
    });
});
