// Preparation Period JavaScript
console.log("📋 صفحة المشاريع في فترة التحضير");

let projectsData = [];

// جلب قائمة المشاريع عند فتح الـ Modal
document.addEventListener("DOMContentLoaded", function () {
    const addPreparationModal = document.getElementById("addPreparationModal");
    if (addPreparationModal) {
        addPreparationModal.addEventListener("show.bs.modal", function () {
            loadProjectsList();
        });
    }

    // عند الكتابة أو الاختيار من الـ datalist
    const projectSearchInput = document.getElementById("project_search");
    if (projectSearchInput) {
        projectSearchInput.addEventListener("input", handleProjectSearch);
    }

    // حساب عدد الأيام عند تغيير التواريخ
    const startDateInput = document.getElementById("preparation_start_date");
    const endDateInput = document.getElementById("preparation_end_date");

    if (startDateInput) {
        startDateInput.addEventListener("change", calculateDays);
    }
    if (endDateInput) {
        endDateInput.addEventListener("change", calculateDays);
    }

    // إعادة تعيين الفورم عند إغلاق الـ Modal
    if (addPreparationModal) {
        addPreparationModal.addEventListener("hidden.bs.modal", function () {
            resetForm();
        });
    }

    // إعادة فتح الـ Modal إذا كانت هناك أخطاء
    const hasErrors = document.querySelector("[data-has-errors]");
    if (hasErrors && hasErrors.dataset.hasErrors === "true") {
        const modalElement = document.getElementById("addPreparationModal");
        if (modalElement) {
            const addModal = new bootstrap.Modal(modalElement);
            addModal.show();
        }
    }
});

// تحميل قائمة المشاريع
function loadProjectsList() {
    const datalistElement = document.getElementById("projectsList");
    const searchInput = document.getElementById("project_search");
    const routeUrl = document.querySelector("[data-projects-list-route]")
        ?.dataset.projectsListRoute;

    if (!datalistElement || !routeUrl) return;

    // عرض loader
    if (searchInput) {
        searchInput.placeholder = "جاري تحميل المشاريع...";
        searchInput.disabled = true;
    }

    fetch(routeUrl, {
        headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN":
                document.querySelector('meta[name="csrf-token"]')?.content ||
                "",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success && data.projects) {
                projectsData = data.projects;

                // ملء الـ datalist
                datalistElement.innerHTML = "";
                data.projects.forEach((project) => {
                    const option = document.createElement("option");
                    option.value = project.display_text;
                    option.dataset.id = project.id;
                    option.dataset.code = project.code;
                    option.dataset.name = project.name;
                    option.dataset.client = project.client_name;
                    datalistElement.appendChild(option);
                });

                if (searchInput) {
                    searchInput.disabled = false;
                    searchInput.placeholder =
                        "ابحث عن المشروع بالكود أو الاسم...";
                }
            } else {
                alert("حدث خطأ أثناء تحميل المشاريع");
                if (searchInput) {
                    searchInput.placeholder = "لا توجد مشاريع متاحة";
                }
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            alert("حدث خطأ أثناء تحميل المشاريع");
            if (searchInput) {
                searchInput.placeholder = "خطأ في التحميل";
            }
        });
}

// معالجة البحث عن المشروع
function handleProjectSearch() {
    const searchValue = this.value.trim();
    const hiddenInput = document.getElementById("project_id");

    if (!searchValue) {
        if (hiddenInput) hiddenInput.value = "";
        const projectInfo = document.getElementById("projectInfo");
        if (projectInfo) projectInfo.style.display = "none";
        const submitBtn = document.getElementById("submitBtn");
        if (submitBtn) submitBtn.disabled = true;
        return;
    }

    // البحث عن المشروع المطابق
    const selectedProject = projectsData.find(
        (p) => p.display_text === searchValue
    );

    if (selectedProject) {
        // تعيين الـ ID في الـ hidden input
        if (hiddenInput) hiddenInput.value = selectedProject.id;

        // عرض معلومات المشروع
        const projectCode = document.getElementById("projectCode");
        const projectName = document.getElementById("projectName");
        const projectClient = document.getElementById("projectClient");
        const projectInfo = document.getElementById("projectInfo");
        const submitBtn = document.getElementById("submitBtn");

        if (projectCode)
            projectCode.textContent = selectedProject.code || "غير محدد";
        if (projectName)
            projectName.textContent = selectedProject.name || "غير محدد";
        if (projectClient)
            projectClient.textContent =
                selectedProject.client_name || "غير محدد";
        if (projectInfo) projectInfo.style.display = "block";
        if (submitBtn) submitBtn.disabled = false;

        // تعيين تاريخ افتراضي (الآن)
        const now = new Date();
        const nowStr = now.toISOString().slice(0, 16);
        const startDateInput = document.getElementById(
            "preparation_start_date"
        );
        if (startDateInput) startDateInput.value = nowStr;

        // تعيين تاريخ النهاية (بعد 7 أيام مثلاً)
        const endDate = new Date(now);
        endDate.setDate(endDate.getDate() + 7);
        const endStr = endDate.toISOString().slice(0, 16);
        const endDateInput = document.getElementById("preparation_end_date");
        if (endDateInput) endDateInput.value = endStr;

        // حساب عدد الأيام
        calculateDays();
    } else {
        // لم يتم العثور على مطابقة
        if (hiddenInput) hiddenInput.value = "";
        const projectInfo = document.getElementById("projectInfo");
        if (projectInfo) projectInfo.style.display = "none";
        const submitBtn = document.getElementById("submitBtn");
        if (submitBtn) submitBtn.disabled = true;
    }
}

// حساب عدد الأيام تلقائياً
function calculateDays() {
    const startDateInput = document.getElementById("preparation_start_date");
    const endDateInput = document.getElementById("preparation_end_date");
    const calculatedDaysElement = document.getElementById("calculatedDays");

    if (!startDateInput || !endDateInput || !calculatedDaysElement) return;

    const startDate = startDateInput.value;
    const endDate = endDateInput.value;

    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        calculatedDaysElement.textContent = diffDays + " يوم";
    }
}

// إعادة تعيين الفورم
function resetForm() {
    const form = document.getElementById("preparationForm");
    const projectSearch = document.getElementById("project_search");
    const projectId = document.getElementById("project_id");
    const projectInfo = document.getElementById("projectInfo");
    const submitBtn = document.getElementById("submitBtn");
    const calculatedDays = document.getElementById("calculatedDays");

    if (form) form.reset();
    if (projectSearch) projectSearch.value = "";
    if (projectId) projectId.value = "";
    if (projectInfo) projectInfo.style.display = "none";
    if (submitBtn) submitBtn.disabled = true;
    if (calculatedDays) calculatedDays.textContent = "0 يوم";
}
