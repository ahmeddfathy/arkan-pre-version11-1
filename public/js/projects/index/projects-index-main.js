/**
 * Projects Index Main JavaScript - OPTIMIZED FOR PERFORMANCE
 * Handles main functionality for projects index page
 */

// Debounce utility function for better scroll performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize progress bars with smooth animation - OPTIMIZED
    function initializeProgressBars() {
        const progressBars = document.querySelectorAll('.projects-progress-fill, .progress-bar[data-progress]');

        progressBars.forEach(bar => {
            const progress = bar.getAttribute('data-progress');
            if (progress) {
                bar.style.width = progress + '%';
            }
        });
    }

    // Initialize progress bars on page load
    initializeProgressBars();

    // Function to update delivery date columns based on selected date type
    function updateDeliveryDateColumns(dateType) {
        // Update table view
        const deliveryDateCells = document.querySelectorAll('.delivery-date-cell');

        deliveryDateCells.forEach(cell => {
            const clientDate = cell.getAttribute('data-client-date');
            const teamDate = cell.getAttribute('data-team-date');

            let dateToShow = '';
            let icon = '';
            let colorClass = '';

            if (dateType === 'client_agreed') {
                dateToShow = clientDate;
                icon = '🤝';
                colorClass = 'text-success';
            } else if (dateType === 'team_delivery') {
                dateToShow = teamDate;
                icon = '👥';
                colorClass = 'text-primary';
            }

            if (dateToShow && dateToShow !== '') {
                cell.innerHTML = `<span class="fw-bold ${colorClass}">${icon} ${dateToShow}</span>`;
            } else {
                cell.innerHTML = '<span class="text-muted">🚫 غير محدد</span>';
            }
        });

        // Update kanban view
        const kanbanDeliveryDates = document.querySelectorAll('.kanban-delivery-date');

        kanbanDeliveryDates.forEach(dateElement => {
            const clientDate = dateElement.getAttribute('data-client-date');
            const teamDate = dateElement.getAttribute('data-team-date');

            let dateToShow = '';

            if (dateType === 'client_agreed') {
                dateToShow = clientDate;
            } else if (dateType === 'team_delivery') {
                dateToShow = teamDate;
            }

            if (dateToShow && dateToShow !== '') {
                dateElement.textContent = dateToShow;
            } else {
                dateElement.textContent = 'غير محدد';
            }
        });

        // Update table header
        const deliveryDateHeader = document.getElementById('deliveryDateHeader');
        if (deliveryDateHeader) {
            if (dateType === 'client_agreed') {
                deliveryDateHeader.innerHTML = '🤝 متفق مع العميل';
            } else if (dateType === 'team_delivery') {
                deliveryDateHeader.innerHTML = '👥 محدد من الفريق';
            }
        }
    }

    // Date type filter change handler
    const dateTypeFilter = document.getElementById('dateTypeFilter');
    if (dateTypeFilter) {
        dateTypeFilter.addEventListener('change', function() {
            updateDeliveryDateColumns(this.value);
        });

        // Initialize with default value
        updateDeliveryDateColumns(dateTypeFilter.value);
    }

    // View Toggle Functionality with Calendar Support
    const tableViewBtn = document.getElementById('tableViewBtn');
    const kanbanViewBtn = document.getElementById('kanbanViewBtn');
    const calendarViewBtn = document.getElementById('calendarViewBtn');

    // Add active class management for modern buttons
    function updateViewButtons(activeBtn) {
        [tableViewBtn, kanbanViewBtn, calendarViewBtn].forEach(btn => {
            if (btn) btn.classList.remove('active');
        });
        if (activeBtn) activeBtn.classList.add('active');
    }

    const tableView = document.getElementById('tableView');
    const kanbanView = document.getElementById('kanbanView');
    const calendarView = document.getElementById('calendarView');

    function switchToView(viewType) {
        // Remove active class from all buttons
        [tableViewBtn, kanbanViewBtn, calendarViewBtn].forEach(btn => {
            if (btn) btn.classList.remove('active');
        });

        // Hide all views
        if (tableView) tableView.style.display = 'none';
        if (kanbanView) kanbanView.style.display = 'none';
        if (calendarView) calendarView.style.display = 'none';

        // Show selected view and activate button
        switch (viewType) {
            case 'table':
                if (tableView) tableView.style.display = 'block';
                updateViewButtons(tableViewBtn);
                break;
            case 'kanban':
                if (kanbanView) kanbanView.style.display = 'block';
                updateViewButtons(kanbanViewBtn);
                break;
            case 'calendar':
                if (calendarView) calendarView.style.display = 'block';
                updateViewButtons(calendarViewBtn);
                // Refresh calendar when switching to it
                if (window.projectsCalendar) {
                    window.projectsCalendar.refresh();
                }
                break;
        }

        // Save preference
        localStorage.setItem('projectsView', viewType);
        console.log(`✅ Switched to ${viewType} view`);
    }

    // Add event listeners - OPTIMIZED (removed animations for performance)
    if (tableViewBtn) {
        tableViewBtn.addEventListener('click', (e) => {
            e.preventDefault();
            switchToView('table');
        });
    }

    if (kanbanViewBtn) {
        kanbanViewBtn.addEventListener('click', (e) => {
            e.preventDefault();
            switchToView('kanban');
        });
    }

    if (calendarViewBtn) {
        calendarViewBtn.addEventListener('click', (e) => {
            e.preventDefault();
            switchToView('calendar');
        });
    }

    // Apply saved preference or default to table
    const savedView = localStorage.getItem('projectsView') || 'table';

    // Small delay to ensure all elements are loaded
    setTimeout(() => {
        switchToView(savedView);
    }, 100);

    // Combined Filters Function - Apply all filters together
    function applyAllFilters() {
        const searchText = document.getElementById('searchProject')?.value.toLowerCase() || '';
        const statusFilter = document.getElementById('statusFilter')?.value.toLowerCase() || '';
        const clientFilter = document.getElementById('clientFilter')?.value.toLowerCase() || '';
        const selectedDate = document.getElementById('monthYearFilter')?.value || '';
        const deliveryMonth = document.getElementById('deliveryMonthFilter')?.value || '';
        const dateTypeFilter = document.getElementById('dateTypeFilter')?.value || 'client_agreed';

        // Convert month filter to project_month_year format
        let projectMonthYearFilter = '';
        if (selectedDate) {
            const [year, month] = selectedDate.split('-');
            projectMonthYearFilter = parseInt(month) + '-' + year; // Remove leading zero from month
        }

        console.log('Applying filters:', {
            search: searchText,
            status: statusFilter,
            client: clientFilter,
            monthYear: projectMonthYearFilter,
            deliveryMonth: deliveryMonth,
            dateType: dateTypeFilter
        });

        // Filter table view
        let tableRows = document.querySelectorAll('.project-row');
        tableRows.forEach(row => {
            let showRow = true;

            // Search filter
            if (searchText) {
                const projectName = row.cells[0].textContent.toLowerCase();
                const clientName = row.cells[2].textContent.toLowerCase();
                const manager = row.cells[3].textContent.toLowerCase();

                if (!projectName.includes(searchText) &&
                    !clientName.includes(searchText) &&
                    !manager.includes(searchText)) {
                    showRow = false;
                }
            }

            // Status filter
            if (showRow && statusFilter) {
                const projectStatus = row.cells[4].textContent.toLowerCase();
                if (!projectStatus.includes(statusFilter)) {
                    showRow = false;
                }
            }

            // Month/Year filter
            if (showRow && projectMonthYearFilter) {
                const rowProjectMonthYear = row.getAttribute('data-project-month-year');
                if (rowProjectMonthYear !== projectMonthYearFilter) {
                    showRow = false;
                }
            }

            // Delivery Month filter
            if (showRow && deliveryMonth) {
                let matchesDeliveryMonth = false;

                // Check based on selected date type
                if (dateTypeFilter === 'client_agreed') {
                    const clientDelivery = row.getAttribute('data-client-delivery');
                    if (clientDelivery === deliveryMonth) {
                        matchesDeliveryMonth = true;
                    }
                } else if (dateTypeFilter === 'team_delivery') {
                    const teamDelivery = row.getAttribute('data-team-delivery');
                    if (teamDelivery === deliveryMonth) {
                        matchesDeliveryMonth = true;
                    }
                }

                // ✅ تم إزالة fallback للـ actual_delivery لتحسين دقة الفلتر
                // الآن الفلتر يعرض فقط المشاريع اللي تطابق نوع التاريخ المختار بالضبط

                if (!matchesDeliveryMonth) {
                    showRow = false;
                }
            }

            // Client filter (if implemented)
            if (showRow && clientFilter) {
                const clientName = row.cells[2].textContent.toLowerCase();
                if (!clientName.includes(clientFilter)) {
                    showRow = false;
                }
            }

            row.style.display = showRow ? '' : 'none';
        });

        // Filter kanban view
        let kanbanCards = document.querySelectorAll('#kanbanView .projects-index-kanban-card');
        kanbanCards.forEach(card => {
            let showCard = true;

            // Search filter
            if (searchText) {
                const titleElement = card.querySelector('.projects-index-kanban-card-title');
                const descElement = card.querySelector('.projects-index-kanban-card-description');
                const clientElement = card.querySelector('.projects-index-kanban-card-client');

                const projectName = titleElement?.textContent.toLowerCase() || '';
                const projectDescription = descElement?.textContent.toLowerCase() || '';
                const clientText = clientElement?.textContent.toLowerCase() || '';

                if (!projectName.includes(searchText) &&
                    !projectDescription.includes(searchText) &&
                    !clientText.includes(searchText)) {
                    showCard = false;
                }
            }

            // Status filter
            if (showCard && statusFilter) {
                const projectStatus = card.getAttribute('data-status')?.toLowerCase() || '';
                if (!projectStatus.includes(statusFilter)) {
                    showCard = false;
                }
            }

            // Month/Year filter
            if (showCard && projectMonthYearFilter) {
                const cardProjectMonthYear = card.getAttribute('data-project-month-year');
                if (cardProjectMonthYear !== projectMonthYearFilter) {
                    showCard = false;
                }
            }

            // Delivery Month filter
            if (showCard && deliveryMonth) {
                let matchesDeliveryMonth = false;

                // Check based on selected date type
                if (dateTypeFilter === 'client_agreed') {
                    const clientDelivery = card.getAttribute('data-client-delivery');
                    if (clientDelivery === deliveryMonth) {
                        matchesDeliveryMonth = true;
                    }
                } else if (dateTypeFilter === 'team_delivery') {
                    const teamDelivery = card.getAttribute('data-team-delivery');
                    if (teamDelivery === deliveryMonth) {
                        matchesDeliveryMonth = true;
                    }
                }


                if (!matchesDeliveryMonth) {
                    showCard = false;
                }
            }

            // Client filter (if implemented)
            if (showCard && clientFilter) {
                const clientElement = card.querySelector('.projects-index-kanban-card-client');
                const clientText = clientElement?.textContent.toLowerCase() || '';
                if (!clientText.includes(clientFilter)) {
                    showCard = false;
                }
            }

            card.style.display = showCard ? '' : 'none';
        });

        // Update kanban column counts
        updateKanbanColumnCounts();

        // Update filter status indicator
        updateFilterStatusIndicator();
    }

    // Show active filters indicator
    function updateFilterStatusIndicator() {
        const searchText = document.getElementById('searchProject')?.value || '';
        const statusFilter = document.getElementById('statusFilter')?.value || '';
        const clientFilter = document.getElementById('clientFilter')?.value || '';
        const monthYearFilter = document.getElementById('monthYearFilter')?.value || '';
        const deliveryMonth = document.getElementById('deliveryMonthFilter')?.value || '';

        const activeFilters = [];
        if (searchText) activeFilters.push(`البحث: "${searchText}"`);
        if (statusFilter) activeFilters.push(`الحالة: ${statusFilter}`);
        if (clientFilter) activeFilters.push(`العميل: ${clientFilter}`);
        if (monthYearFilter) {
            const [year, month] = monthYearFilter.split('-');
            const monthNames = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
            activeFilters.push(`الشهر: ${monthNames[parseInt(month)]} ${year}`);
        }
        if (deliveryMonth) {
            const [year, month] = deliveryMonth.split('-');
            const monthNames = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
            activeFilters.push(`شهر الاستلام: ${monthNames[parseInt(month)]} ${year}`);
        }

        // Update visual indicator (you can add this to your HTML if needed)
        console.log('Active filters:', activeFilters.length ? activeFilters : ['لا توجد فلاتر مفعلة']);
    }

    // Update kanban column project counts
    function updateKanbanColumnCounts() {
        document.querySelectorAll('.projects-index-kanban-column').forEach(column => {
            const visibleCards = column.querySelectorAll('.projects-index-kanban-card:not([style*="display: none"])');
            const countElement = column.querySelector('.project-count');
            if (countElement) {
                countElement.textContent = visibleCards.length;
            }
        });
    }

    // Attach event listeners to all filters - WITH DEBOUNCING FOR PERFORMANCE
    const debouncedApplyFilters = debounce(applyAllFilters, 300);

    document.getElementById('searchProject')?.addEventListener('keyup', debouncedApplyFilters);
    document.getElementById('statusFilter')?.addEventListener('change', applyAllFilters);
    document.getElementById('clientFilter')?.addEventListener('change', applyAllFilters);
    document.getElementById('monthYearFilter')?.addEventListener('change', applyAllFilters);
    document.getElementById('deliveryMonthFilter')?.addEventListener('change', applyAllFilters);
    document.getElementById('dateTypeFilter')?.addEventListener('change', applyAllFilters);

    // Reset Filters Button
    document.getElementById('resetFiltersBtn')?.addEventListener('click', function() {
        // Reset all filter values
        document.getElementById('searchProject').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('clientFilter').value = '';
        document.getElementById('monthYearFilter').value = '';
        document.getElementById('deliveryMonthFilter').value = '';
        document.getElementById('dateTypeFilter').value = 'client_agreed';

        // Apply filters (which will show all projects since all filters are empty)
        applyAllFilters();

        // Reset delivery date columns to default
        updateDeliveryDateColumns('client_agreed');

        // Update calendar if available
        if (window.projectsCalendar) {
            window.projectsCalendar.applyFilters();
        }

        // Show success message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'تم إعادة التعيين!',
                text: 'تم إعادة تعيين جميع الفلاتر بنجاح',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        console.log('✅ All filters have been reset');
    });

    // Also update calendar when filters change - WITH DEBOUNCING
    const debouncedUpdateCalendar = debounce(function() {
        if (window.projectsCalendar) {
            window.projectsCalendar.applyFilters();
        }
    }, 300);

    // Add calendar update to the existing filter event listeners
    ['searchProject', 'statusFilter', 'clientFilter', 'monthYearFilter', 'deliveryMonthFilter'].forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            if (filterId === 'searchProject') {
                element.addEventListener('keyup', debouncedUpdateCalendar);
            } else {
                element.addEventListener('change', debouncedUpdateCalendar);
            }
        }
    });

    // Separate handler for dateTypeFilter since it affects delivery date display
    document.getElementById('dateTypeFilter')?.addEventListener('change', function() {
        updateDeliveryDateColumns(this.value);
        updateDeliveryMonthLabel(this.value);
        updateCalendarFilters();
    });

    // Function to update delivery month label based on selected date type
    function updateDeliveryMonthLabel(dateType) {
        const deliveryMonthLabel = document.getElementById('deliveryMonthLabel');
        const deliveryMonthInput = document.getElementById('deliveryMonthFilter');
        const dateTypeFilter = document.getElementById('dateTypeFilter');

        if (deliveryMonthLabel && deliveryMonthInput) {
            // Add highlight effect to date type filter
            if (dateTypeFilter) {
                dateTypeFilter.classList.add('active-connection');
                setTimeout(() => {
                    dateTypeFilter.classList.remove('active-connection');
                }, 2000);
            }

            if (dateType === 'client_agreed') {
                deliveryMonthLabel.innerHTML = '🚚 شهر التسليم (متفق مع العميل)';
                deliveryMonthLabel.style.background = 'rgba(34, 197, 94, 0.8)';
                deliveryMonthLabel.style.borderColor = 'rgba(34, 197, 94, 0.4)';
                deliveryMonthLabel.style.color = '#ffffff';
                deliveryMonthInput.title = 'فلتر المشاريع حسب شهر التسليم المتفق عليه مع العميل';
                deliveryMonthInput.placeholder = 'شهر التسليم للعميل';
            } else if (dateType === 'team_delivery') {
                deliveryMonthLabel.innerHTML = '🚚 شهر التسليم (محدد من الفريق)';
                deliveryMonthLabel.style.background = 'rgba(59, 130, 246, 0.8)';
                deliveryMonthLabel.style.borderColor = 'rgba(59, 130, 246, 0.4)';
                deliveryMonthLabel.style.color = '#ffffff';
                deliveryMonthInput.title = 'فلتر المشاريع حسب شهر التسليم المحدد من الفريق';
                deliveryMonthInput.placeholder = 'شهر التسليم للفريق';
            }

            // Show toast notification
            if (typeof Swal !== 'undefined') {
                const message = dateType === 'client_agreed'
                    ? 'سيتم البحث في المواعيد المتفق عليها مع العميل'
                    : 'سيتم البحث في المواعيد المحددة من الفريق';

                Swal.fire({
                    title: 'تم تحديث نوع التاريخ!',
                    text: message,
                    icon: 'info',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        }
    }

    // Initialize delivery month label on page load
    updateDeliveryMonthLabel('client_agreed');

    // ⚡ PERFORMANCE: Add scroll class for optimizations
    let scrollTimeout;
    let isScrolling = false;

    window.addEventListener('scroll', function() {
        if (!isScrolling) {
            document.body.classList.add('scrolling');
            isScrolling = true;
        }

        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(function() {
            document.body.classList.remove('scrolling');
            isScrolling = false;
        }, 150);
    }, { passive: true });

    // ⚡ PERFORMANCE: Optimize table row visibility
    if ('IntersectionObserver' in window) {
        const observerOptions = {
            root: null,
            rootMargin: '50px',
            threshold: 0.01
        };

        const rowObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.contentVisibility = 'visible';
                } else {
                    entry.target.style.contentVisibility = 'auto';
                }
            });
        }, observerOptions);

        // Observe all table rows
        const tableRows = document.querySelectorAll('.project-row');
        tableRows.forEach(row => rowObserver.observe(row));

        // Observe kanban cards
        const kanbanCards = document.querySelectorAll('.projects-index-kanban-card');
        kanbanCards.forEach(card => rowObserver.observe(card));
    }

    console.log('✅ Performance optimizations applied');
});
