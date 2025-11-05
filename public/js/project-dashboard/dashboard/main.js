document.addEventListener("DOMContentLoaded", function () {
    // Chart Colors
    const colors = {
        primary: "#9b59b6",
        success: "#23c277",
        warning: "#c0392b",
        info: "#4BAAD4",
        secondary: "#6c757d",
    };

    // Project Status Chart
    const projectStatusCanvas = document.getElementById("projectStatusChart");
    if (projectStatusCanvas) {
        const projectCtx = projectStatusCanvas.getContext("2d");
        new Chart(projectCtx, {
            type: "doughnut",
            data: {
                labels: ["جديدة", "قيد التنفيذ", "مكتملة", "موقوفة", "متأخرة"],
                datasets: [
                    {
                        data: [
                            window.projectData?.newProjects || 0,
                            window.projectData?.inProgressProjects || 0,
                            window.projectData?.completedProjects || 0,
                            window.projectData?.pausedProjects || 0,
                            window.projectData?.overdueProjectsCount || 0,
                        ],
                        backgroundColor: [
                            colors.info,
                            colors.primary,
                            colors.success,
                            "#e74c3c",
                            "#ffad46",
                        ],
                        borderWidth: 0,
                        cutout: "70%",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 14,
                                family: "Cairo",
                            },
                        },
                    },
                },
            },
        });
    } else {
        console.error("projectStatusChart element not found");
    }

    // Tasks Chart
    const tasksCanvas = document.getElementById("tasksChart");
    if (tasksCanvas) {
        const tasksCtx = tasksCanvas.getContext("2d");
        new Chart(tasksCtx, {
            type: "bar",
            data: {
                labels: ["جديدة", "قيد التنفيذ", "متوقفة", "مكتملة"],
                datasets: [
                    {
                        data: [
                            window.taskData?.allNewTasks || 0,
                            window.taskData?.allInProgressTasks || 0,
                            window.taskData?.allPausedTasks || 0,
                            window.taskData?.allCompletedTasks || 0,
                        ],
                        backgroundColor: [
                            colors.info,
                            colors.primary,
                            colors.warning,
                            colors.success,
                        ],
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "rgba(0,0,0,0.1)",
                        },
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                    },
                },
            },
        });
    } else {
        console.error("tasksChart element not found");
    }

    // Dashboard Filters functionality
    initializeDashboardFilters();

    function initializeDashboardFilters() {
        const toggleBtn = document.getElementById("toggleFiltersBtn");
        const filtersContent = document.getElementById("filtersContent");
        const quickFilterRadio = document.getElementById("quick_filter");
        const customFilterRadio = document.getElementById("custom_filter");
        const quickFilterGroup = document.getElementById("quickFilterGroup");
        const customFilterGroups = document.querySelectorAll(".custom-filter");

        // Toggle filters visibility
        if (toggleBtn && filtersContent) {
            toggleBtn.addEventListener("click", function () {
                const isCollapsed =
                    filtersContent.classList.contains("collapsed");
                if (isCollapsed) {
                    filtersContent.classList.remove("collapsed");
                    this.innerHTML =
                        '<i class="fas fa-chevron-up"></i> إخفاء الفلاتر';
                } else {
                    filtersContent.classList.add("collapsed");
                    this.innerHTML =
                        '<i class="fas fa-chevron-down"></i> عرض الفلاتر';
                }
            });
        }

        // Handle filter type switching
        function toggleFilterType() {
            if (quickFilterRadio && quickFilterRadio.checked) {
                quickFilterGroup.style.display = "flex";
                customFilterGroups.forEach((group) => {
                    group.style.display = "none";
                });
            } else if (customFilterRadio && customFilterRadio.checked) {
                quickFilterGroup.style.display = "none";
                customFilterGroups.forEach((group) => {
                    group.style.display = "flex";
                });
            }
        }

        // Initial state
        toggleFilterType();

        // Event listeners for radio buttons
        if (quickFilterRadio) {
            quickFilterRadio.addEventListener("change", toggleFilterType);
        }
        if (customFilterRadio) {
            customFilterRadio.addEventListener("change", toggleFilterType);
        }

        // Auto-submit form when quick period changes
        const quickPeriodSelect = document.getElementById("quickPeriod");
        if (quickPeriodSelect) {
            quickPeriodSelect.addEventListener("change", function () {
                if (this.value && quickFilterRadio.checked) {
                    document.getElementById("dashboardFiltersForm").submit();
                }
            });
        }

        // Validate custom date range
        const fromDate = document.getElementById("fromDate");
        const toDate = document.getElementById("toDate");

        if (fromDate && toDate) {
            fromDate.addEventListener("change", function () {
                toDate.setAttribute("min", this.value);
            });

            toDate.addEventListener("change", function () {
                fromDate.setAttribute("max", this.value);
            });
        }
    }
});
