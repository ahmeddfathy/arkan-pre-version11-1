// Modal Handling for Overtime Requests
document.addEventListener('DOMContentLoaded', function() {
    // تأكد من أن Bootstrap Modal متاح
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded!');
        return;
    }

    // Edit Modal Handler
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const request = JSON.parse(this.dataset.request);
            const form = document.getElementById('editOvertimeForm');
            form.action = `/overtime-requests/${request.id}`;

            // Format the date to YYYY-MM-DD
            const formattedDate = request.overtime_date.split('T')[0];
            document.getElementById('edit_overtime_date').value = formattedDate;

            // Get the original time values (assuming they are in HH:mm format)
            const startTime = request.start_time;
            const endTime = request.end_time;

            document.getElementById('edit_start_time').value = startTime;
            document.getElementById('edit_end_time').value = endTime;
            document.getElementById('edit_reason').value = request.reason;

            // Update character count for reason
            const reasonCharCount = document.getElementById('reasonCharCount');
            if (reasonCharCount) {
                reasonCharCount.textContent = request.reason.length;
            }

            // Add validation for end time being after start time
            document.getElementById('edit_end_time').min = startTime;
        });
    });

    // Response Modal Handler
    const responseButtons = document.querySelectorAll('.respond-btn');
    responseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const responseType = this.dataset.responseType;
            const form = document.getElementById('respondOvertimeForm');

            form.action = `/overtime-requests/${requestId}/${responseType}-status`;
            document.getElementById('response_type').value = responseType;

            // Reset the form
            document.getElementById('response_status').value = 'approved';
            document.getElementById('rejection_reason_container').style.display = 'none';
            document.getElementById('rejection_reason').value = '';
            document.getElementById('rejection_reason').required = false;

            // Add event listener to the select to show/hide reason field
            document.getElementById('response_status').addEventListener('change', function() {
                if (this.value === 'approved') {
                    document.getElementById('rejection_reason_container').style.display = 'none';
                    document.getElementById('rejection_reason').required = false;
                } else {
                    document.getElementById('rejection_reason_container').style.display = 'block';
                    document.getElementById('rejection_reason').required = true;
                }
            });
        });
    });

    // Delete Confirmation
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (confirm('هل أنت متأكد من حذف هذا الطلب؟')) {
                fetch(this.action, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw response;
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'حدث خطأ أثناء حذف الطلب');
                    }
                })
                .catch(async error => {
                    let errorMessage = 'حدث خطأ أثناء حذف الطلب';
                    if (error instanceof Response) {
                        try {
                            const errorData = await error.json();
                            errorMessage = errorData.message || errorMessage;
                        } catch (e) {}
                    }
                    alert(errorMessage);
                });
            }
        });
    });

    // Modify Response Handler
    const modifyResponseButtons = document.querySelectorAll('.modify-response-btn');
    modifyResponseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const responseType = this.dataset.responseType;
            const currentStatus = this.dataset.currentStatus;
            const currentReason = this.dataset.currentReason;
            const form = document.getElementById('modifyResponseForm');

            form.action = `/overtime-requests/${requestId}/modify-${responseType}-status`;
            document.getElementById('modify_response_type').value = responseType;

            // Set the select box value based on current status
            document.getElementById('modify_status').value = currentStatus;

            // Show/hide rejection reason based on selection
            if (currentStatus === 'approved') {
                document.getElementById('modify_rejection_reason_container').style.display = 'none';
                document.getElementById('modify_rejection_reason').required = false;
            } else {
                document.getElementById('modify_rejection_reason_container').style.display = 'block';
                document.getElementById('modify_rejection_reason').value = currentReason;
                document.getElementById('modify_rejection_reason').required = true;
            }

            // Add event listener to the select to show/hide reason field
            document.getElementById('modify_status').addEventListener('change', function() {
                if (this.value === 'approved') {
                    document.getElementById('modify_rejection_reason_container').style.display = 'none';
                    document.getElementById('modify_rejection_reason').required = false;
                } else {
                    document.getElementById('modify_rejection_reason_container').style.display = 'block';
                    document.getElementById('modify_rejection_reason').required = true;
                }
            });
        });
    });

    // Department Details Modal
    const departmentEmployees = JSON.parse(document.getElementById('departmentData')?.getAttribute('data-employees') || '{}');
    const modal = document.getElementById('departmentDetailsModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const department = button.dataset.department;
            const employees = departmentEmployees[department] || [];

            document.getElementById('departmentName').textContent = department;
            const tbody = document.getElementById('departmentEmployees');
            tbody.innerHTML = '';

            employees.forEach(employee => {
                tbody.innerHTML += `
                    <tr>
                        <td>${employee.name}</td>
                        <td>${employee.total_requests}</td>
                        <td>${employee.approved_requests}</td>
                        <td>${employee.rejected_requests}</td>
                        <td>${employee.pending_requests}</td>
                        <td>${Number(employee.total_requested_hours).toFixed(1)}</td>
                        <td>${Number(employee.approved_hours).toFixed(1)}</td>
                    </tr>
                `;
            });
        });
    }

    // معالج خاص لأزرار رد HR للتأكد من فتح المودال
    document.querySelectorAll('.respond-btn[data-response-type="hr"]').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const responseType = this.dataset.responseType;
            const form = document.getElementById('respondOvertimeForm');

            // تعيين مسار النموذج
            form.action = `/overtime-requests/${requestId}/hr-status`;
            document.getElementById('response_type').value = responseType;
        });
    });
});
