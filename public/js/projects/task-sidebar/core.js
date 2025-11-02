// Global variable to store sidebar timer interval
let sidebarTimerInterval = null;

// Task Sidebar Core Functions
function openTaskSidebar(taskType, taskUserId) {
    const sidebar = document.getElementById("taskSidebar");
    const overlay = document.getElementById("sidebarOverlay");

    // Show overlay
    overlay.style.visibility = "visible";
    overlay.style.opacity = "1";

    // Show sidebar
    sidebar.style.left = "0";

    // Add sidebar-open class to body to prevent horizontal scrolling only
    document.body.classList.add("sidebar-open");
    document.documentElement.classList.add("sidebar-open");

    // Load task details
    loadTaskDetails(taskType, taskUserId);
}

function closeTaskSidebar() {
    const sidebar = document.getElementById("taskSidebar");
    const overlay = document.getElementById("sidebarOverlay");

    // Hide sidebar
    sidebar.style.left = "-480px";

    // Stop any running sidebar timer
    stopSidebarTimer();

    // Hide overlay
    overlay.style.opacity = "0";
    setTimeout(() => {
        overlay.style.visibility = "hidden";
    }, 300);

    // Remove sidebar-open class from body to restore scrolling
    document.body.classList.remove("sidebar-open");
    document.documentElement.classList.remove("sidebar-open");
}

function loadTaskDetails(taskType, taskUserId) {
    const content = document.getElementById("taskSidebarContent");
    const title = document.getElementById("taskSidebarTitle");
    const subtitle = document.getElementById("taskSidebarSubtitle");

    // Validate parameters
    if (!taskType || !taskUserId) {
        console.error("❌ Missing parameters:", { taskType, taskUserId });
        showError("معاملات غير صحيحة لفتح المهمة");
        return;
    }

    // Clean taskUserId - remove any trailing slashes or whitespace
    taskUserId = taskUserId.toString().trim().replace(/\/+$/, "");

    console.log("🔍 Loading task details:", { taskType, taskUserId });

    // Show loading state
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
            <p class="mt-3 text-muted">جاري تحميل تفاصيل المهمة...</p>
        </div>
    `;

    // Get current user ID from various sources
    const getCurrentUserId = () => {
        // Try different methods to get current user ID
        return (
            window.currentUserId ||
            document
                .querySelector('meta[name="user-id"]')
                ?.getAttribute("content") ||
            document.querySelector("[data-user-id]")?.dataset.userId ||
            document.querySelector("[data-current-user-id]")?.dataset
                .currentUserId ||
            null
        );
    };

    window.currentUserId = getCurrentUserId();

    // Make API call to get task details
    fetch(`/task-details/${taskType}/${taskUserId}`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // إذا كانت المهمة غير مُعيَّنة، أظهر تنبيه
                if (data.warning && data.task.is_unassigned) {
                    showWarning(data.warning);
                }
                displayTaskDetails(data.task);
            } else {
                showError(data.message || "حدث خطأ في تحميل بيانات المهمة");
            }
        })
        .catch((error) => {
            console.error("Error loading task details:", error);
            showError("حدث خطأ في الاتصال بالخادم");
        });
}

function showError(message) {
    const content = document.getElementById("taskSidebarContent");
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="text-danger mb-3" style="font-size: 48px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h6 class="text-danger">${message}</h6>
            <button onclick="closeTaskSidebar()" class="btn btn-outline-secondary mt-3">
                إغلاق
            </button>
        </div>
    `;
}

function showWarning(message) {
    // عرض تنبيه مؤقت في أعلى الـ sidebar
    const sidebar = document.getElementById("taskSidebar");
    const warningDiv = document.createElement("div");
    warningDiv.className =
        "alert alert-warning alert-dismissible fade show m-3";
    warningDiv.innerHTML = `
        <i class="fas fa-info-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // إدراج التنبيه في أعلى الـ sidebar
    const existingWarning = sidebar.querySelector(".alert-warning");
    if (existingWarning) {
        existingWarning.remove();
    }

    sidebar.insertBefore(warningDiv, sidebar.firstChild);

    // إزالة التنبيه تلقائياً بعد 5 ثوانِ
    setTimeout(() => {
        if (warningDiv && warningDiv.parentNode) {
            warningDiv.remove();
        }
    }, 5000);
}

// Helper function to check if user can start a task
function canUserStartTask(task, currentUserId) {
    // If no current user ID available, deny access
    if (!currentUserId) {
        return false;
    }

    // If task has no assigned user, allow anyone to start it
    if (!task.user || !task.user.id) {
        return true;
    }

    // If current user is the task owner, allow
    if (task.user.id == currentUserId) {
        return true;
    }

    // Check if user has HR/Admin privileges (you can extend this logic)
    // For now, we'll be strict and only allow task owners
    return false;
}

// Close sidebar on Escape key
document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
        closeTaskSidebar();
    }
});

// Export functions to window for global access
window.openTaskSidebar = openTaskSidebar;
window.closeTaskSidebar = closeTaskSidebar;
window.loadTaskDetails = loadTaskDetails;
window.showError = showError;
window.showWarning = showWarning;
window.canUserStartTask = canUserStartTask;
