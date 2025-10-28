document.addEventListener('DOMContentLoaded', function () {
    // Team Filters functionality
    initializeTeamFilters();

    function initializeTeamFilters() {
        const toggleBtn = document.getElementById('toggleFiltersBtn');
        const filtersContent = document.getElementById('filtersContent');
        const quickFilterRadio = document.getElementById('quick_filter');
        const customFilterRadio = document.getElementById('custom_filter');
        const quickFilterGroup = document.getElementById('quickFilterGroup');
        const customFilterGroups = document.querySelectorAll('.custom-filter');

        // Toggle filters visibility
        if (toggleBtn && filtersContent) {
            toggleBtn.addEventListener('click', function() {
                const isCollapsed = filtersContent.classList.contains('collapsed');
                if (isCollapsed) {
                    filtersContent.classList.remove('collapsed');
                    this.innerHTML = '<i class="fas fa-chevron-up"></i> إخفاء الفلاتر';
                } else {
                    filtersContent.classList.add('collapsed');
                    this.innerHTML = '<i class="fas fa-chevron-down"></i> عرض الفلاتر';
                }
            });
        }

        // Handle filter type switching
        function toggleFilterType() {
            if (quickFilterRadio && quickFilterRadio.checked) {
                if (quickFilterGroup) quickFilterGroup.style.display = 'flex';
                customFilterGroups.forEach(group => {
                    group.style.display = 'none';
                });
            } else if (customFilterRadio && customFilterRadio.checked) {
                if (quickFilterGroup) quickFilterGroup.style.display = 'none';
                customFilterGroups.forEach(group => {
                    group.style.display = 'flex';
                });
            }
        }

        // Initial state
        toggleFilterType();

        // Event listeners for radio buttons
        if (quickFilterRadio) {
            quickFilterRadio.addEventListener('change', toggleFilterType);
        }
        if (customFilterRadio) {
            customFilterRadio.addEventListener('change', toggleFilterType);
        }

        // Auto-submit form when quick period changes
        const quickPeriodSelect = document.getElementById('quickPeriod');
        if (quickPeriodSelect) {
            quickPeriodSelect.addEventListener('change', function() {
                if (this.value && quickFilterRadio && quickFilterRadio.checked) {
                    document.getElementById('teamFiltersForm').submit();
                }
            });
        }

        // Validate custom date range
        const fromDate = document.getElementById('fromDate');
        const toDate = document.getElementById('toDate');

        if (fromDate && toDate) {
            fromDate.addEventListener('change', function() {
                toDate.setAttribute('min', this.value);
            });

            toDate.addEventListener('change', function() {
                fromDate.setAttribute('max', this.value);
            });
        }
    }
});
