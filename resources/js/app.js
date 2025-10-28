import "./bootstrap";

toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 20000,
    extendedTimeOut: 1000,
    preventDuplicates: true,
    newestOnTop: true,
    showEasing: "swing",
    hideEasing: "linear",
    showMethod: "fadeIn",
    hideMethod: "fadeOut",
};

function checkDuplicateRequest(formData, type) {
    const existingRequests = document.querySelectorAll(".request-row");
    let isDuplicate = false;

    existingRequests.forEach((row) => {
        if (type === "absence") {
            const date = row.children[0].textContent;
            if (
                date === formData.get("absence_date") &&
                row.children[2].textContent.includes("pending")
            ) {
                isDuplicate = true;
            }
        } else if (type === "permission") {
            const datetime = row.children[0].textContent;
            if (
                datetime === formData.get("request_datetime") &&
                row.children[3].textContent.includes("pending")
            ) {
                isDuplicate = true;
            }
        }
    });

    return isDuplicate;
}

document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", function (event) {
        const formData = new FormData(this);

        if (
            this.id === "createAbsenceForm" &&
            checkDuplicateRequest(formData, "absence")
        ) {
            event.preventDefault();
            toastr.error("You already have a pending request for this date.");
            return;
        }

        if (
            this.id === "createPermissionForm" &&
            checkDuplicateRequest(formData, "permission")
        ) {
            event.preventDefault();
            toastr.error(
                "You already have a pending request for this date and time."
            );
            return;
        }

        if (
            this.id === "createPermissionForm" ||
            this.id === "editPermissionForm"
        ) {
            const duration = parseInt(formData.get("duration_minutes"));
            if (duration > 180) {
                event.preventDefault();
                toastr.error("Permission duration cannot exceed 180 minutes.");
                return;
            }
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<div class="loading-spinner"></div>';
        submitBtn.disabled = true;

        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 1000);
    });
});

const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
);
const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
