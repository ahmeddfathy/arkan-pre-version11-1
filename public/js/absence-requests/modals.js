// Modal functionality for absence requests

document.addEventListener('DOMContentLoaded', function() {
    // Edit request handling
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            try {
                const request = JSON.parse(this.dataset.request);
                const form = document.getElementById('editAbsenceForm');
                form.action = `/absence-requests/${request.id}`;

                // تنسيق التاريخ بدون أصفار
                const date = new Date(request.absence_date);
                const formattedDate = date.toISOString().split('T')[0];
                document.getElementById('edit_absence_date').value = formattedDate;

                document.getElementById('edit_reason').value = request.reason;
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });

    // Response handling
    document.querySelectorAll('.respond-btn').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const responseType = this.dataset.responseType;
            const form = document.getElementById('respondForm');

            // تحديث عنوان المودال
            document.getElementById('responseTitle').textContent =
                responseType === 'manager' ? 'رد المدير' : 'رد HR';

            // تحديث نوع الرد
            document.getElementById('response_type').value = responseType;

            // تحديث مسار الفورم
            form.action = `/absence-requests/${requestId}/status`;
        });
    });

    // Show/hide rejection reason field
    document.querySelectorAll('input[name="status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const rejectionContainer = document.getElementById('response_reason_container');
            const rejectionTextarea = document.getElementById('response_reason');

            if (this.value === 'rejected') {
                rejectionContainer.style.display = 'block';
                rejectionTextarea.required = true;
            } else {
                rejectionContainer.style.display = 'none';
                rejectionTextarea.required = false;
            }
        });
    });

    // Handling modify response buttons
    document.querySelectorAll('.modify-response-btn').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const responseType = this.dataset.responseType;
            const form = document.getElementById('modifyResponseForm');

            form.action = `/absence-requests/${requestId}/modify`;

            // تحديث نوع الرد في النموذج
            document.getElementById('modify_response_type').value = responseType;

            // تحديث الحالة وسبب الرفض
            const requestStatus = this.dataset.status;
            const requestReason = this.dataset.reason;

            document.getElementById('modify_status').value = requestStatus;
            document.getElementById('modify_reason').value = requestReason || '';

            // عرض/إخفاء حقل سبب الرفض
            const reasonContainer = document.getElementById('modify_reason_container');
            if (requestStatus === 'rejected') {
                reasonContainer.style.display = 'block';
                document.getElementById('modify_reason').required = true;
            } else {
                reasonContainer.style.display = 'none';
                document.getElementById('modify_reason').required = false;
            }
        });
    });

    // Response status change
    const responseStatusSelect = document.getElementById('response_status');
    if (responseStatusSelect) {
        responseStatusSelect.addEventListener('change', function() {
            const rejectionContainer = document.getElementById('response_reason_container');
            const rejectionTextarea = document.getElementById('response_reason');

            if (this.value === 'rejected') {
                rejectionContainer.style.display = 'block';
                rejectionTextarea.required = true;
            } else {
                rejectionContainer.style.display = 'none';
                rejectionTextarea.required = false;
            }
        });

        // Initialize display status based on selected value
        if (responseStatusSelect.value === 'rejected') {
            document.getElementById('response_reason_container').style.display = 'block';
        }
    }

    // Modify status change
    const modifyStatusSelect = document.getElementById('modify_status');
    if (modifyStatusSelect) {
        modifyStatusSelect.addEventListener('change', function() {
            const rejectionContainer = document.getElementById('modify_reason_container');
            const rejectionTextarea = document.getElementById('modify_reason');

            if (this.value === 'rejected') {
                rejectionContainer.style.display = 'block';
                rejectionTextarea.required = true;
            } else {
                rejectionContainer.style.display = 'none';
                rejectionTextarea.required = false;
            }
        });

        // Initialize display status based on selected value
        if (modifyStatusSelect.value === 'rejected') {
            document.getElementById('modify_reason_container').style.display = 'block';
        }
    }
});
