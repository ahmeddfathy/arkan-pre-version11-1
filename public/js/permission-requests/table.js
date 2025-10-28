function initializeCountdowns() {
    const countdowns = document.querySelectorAll('.countdown');
    countdowns.forEach(countdown => {
        startCountdown(countdown);
    });
}

function handleReturnStatus() {
    document.querySelectorAll('.return-btn, .reset-btn').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const status = this.dataset.status;

            if (status === '2' && !confirm('هل أنت متأكد من تسجيل عدم عودة الموظف؟')) {
                return;
            }

            fetch(`/permission-requests/${requestId}/return-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ return_status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'حدث خطأ أثناء تحديث حالة العودة');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تحديث حالة العودة');
            });
        });
    });
}

function resetStatus(requestId, type) {
    if (confirm('هل أنت متأكد من إعادة تعيين هذا الرد؟')) {
        fetch(`/permission-requests/${requestId}/reset-${type}-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                _token: document.querySelector('meta[name="csrf-token"]').content
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'حدث خطأ أثناء إعادة تعيين الرد');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء إعادة تعيين الرد');
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initializeCountdowns();
    handleReturnStatus();

    $('.datatable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
        }
    });

    $('.select2').select2({
        dir: "rtl"
    });

    // حذف الطلب
    document.querySelectorAll('.delete-request').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('هل أنت متأكد من حذف هذا الطلب؟')) {
                const requestId = this.dataset.requestId;
                fetch(`/permission-requests/${requestId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || 'حدث خطأ أثناء حذف الطلب');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'حدث خطأ أثناء حذف الطلب');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(error.message || 'حدث خطأ أثناء حذف الطلب');
                });
            }
        });
    });

    // تحميل بيانات الطلب في نموذج التعديل
    document.querySelectorAll('[data-bs-target="#editPermissionModal"]').forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.querySelector('#editPermissionModal');
            const form = modal.querySelector('form');

            form.querySelector('[name="departure_time"]').value = this.dataset.departureTime;
            form.querySelector('[name="return_time"]').value = this.dataset.returnTime;
            form.querySelector('[name="reason"]').value = this.dataset.reason;
            form.action = `/permission-requests/${this.dataset.requestId}`;
        });
    });
});

