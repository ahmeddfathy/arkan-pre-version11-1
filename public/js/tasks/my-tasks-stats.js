/**
 * My Tasks Statistics Cards JavaScript
 * Handles dynamic updates and filtering
 */

(function() {
    'use strict';

    // Store original stats data
    let originalStats = {
        total: 0,
        new: 0,
        in_progress: 0,
        paused: 0,
        completed: 0,
        cancelled: 0,
        transferred: 0,
        estimated_hours: 0,
        actual_hours: 0,
        total_points: 0
    };

    // Initialize stats on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeStats();
        attachStatsCardListeners();
    });

    /**
     * Initialize statistics from page data
     */
    function initializeStats() {
        // Get initial values from the cards
        originalStats.total = parseInt(document.getElementById('stat-total')?.textContent || 0);
        originalStats.new = parseInt(document.getElementById('stat-new')?.textContent || 0);
        originalStats.in_progress = parseInt(document.getElementById('stat-in-progress')?.textContent || 0);
        originalStats.paused = parseInt(document.getElementById('stat-paused')?.textContent || 0);
        originalStats.completed = parseInt(document.getElementById('stat-completed')?.textContent || 0);
        originalStats.total_points = parseInt(document.getElementById('stat-points')?.textContent || 0);

        const transferredElement = document.getElementById('stat-transferred');
        if (transferredElement) {
            originalStats.transferred = parseInt(transferredElement.textContent || 0);
        }

        console.log('📊 Statistics initialized:', originalStats);
    }

    /**
     * Attach click listeners to stats cards for filtering
     */
    function attachStatsCardListeners() {
        const statsCards = document.querySelectorAll('.stats-card[data-filter]');

        statsCards.forEach(card => {
            card.addEventListener('click', function(e) {
                e.preventDefault();

                const filterValue = this.getAttribute('data-filter');

                // Toggle active state
                const isActive = this.classList.contains('active-filter');

                // Remove active from all cards
                statsCards.forEach(c => c.classList.remove('active-filter'));

                if (!isActive) {
                    // Activate this card
                    this.classList.add('active-filter');

                    // Apply filter
                    applyStatsFilter(filterValue);
                } else {
                    // Clear filter
                    clearStatsFilter();
                }
            });
        });

        console.log('✅ Stats card listeners attached');
    }

    /**
     * Apply filter based on stats card selection
     */
    function applyStatsFilter(filterValue) {
        const statusFilter = document.getElementById('statusFilter');

        if (filterValue === 'all') {
            // Clear all filters
            if (statusFilter) {
                statusFilter.value = '';
                statusFilter.dispatchEvent(new Event('change'));
            }
        } else if (filterValue === 'transferred') {
            // Filter transferred tasks
            filterTransferredTasks();
        } else {
            // Set status filter
            if (statusFilter) {
                statusFilter.value = filterValue;
                statusFilter.dispatchEvent(new Event('change'));
            }
        }

        console.log('🔍 Applied stats filter:', filterValue);
    }

    /**
     * Clear all stats filters
     */
    function clearStatsFilter() {
        const statusFilter = document.getElementById('statusFilter');

        if (statusFilter) {
            statusFilter.value = '';
            statusFilter.dispatchEvent(new Event('change'));
        }

        // Show all tasks in table and kanban
        showAllTasks();

        console.log('✨ Cleared stats filter');
    }

    /**
     * Filter transferred tasks specifically
     */
    function filterTransferredTasks() {
        // Table view
        const tableRows = document.querySelectorAll('#myTasksTable tbody tr');
        tableRows.forEach(row => {
            const isTransferred = row.getAttribute('data-is-transferred') === 'true';
            row.style.display = isTransferred ? '' : 'none';
        });

        // Kanban view
        const kanbanCards = document.querySelectorAll('.kanban-card');
        kanbanCards.forEach(card => {
            const isTransferred = card.getAttribute('data-is-transferred') === 'true';
            card.style.display = isTransferred ? '' : 'none';
        });

        // Update kanban counters if in kanban view
        if (typeof updateMyTasksKanbanCounters === 'function') {
            updateMyTasksKanbanCounters();
        }
    }

    /**
     * Show all tasks
     */
    function showAllTasks() {
        // Table view
        const tableRows = document.querySelectorAll('#myTasksTable tbody tr');
        tableRows.forEach(row => {
            row.style.display = '';
        });

        // Kanban view
        const kanbanCards = document.querySelectorAll('.kanban-card');
        kanbanCards.forEach(card => {
            card.style.display = '';
        });

        // Update kanban counters if in kanban view
        if (typeof updateMyTasksKanbanCounters === 'function') {
            updateMyTasksKanbanCounters();
        }
    }

    /**
     * Update statistics based on visible tasks
     * Called after filtering
     */
    function updateVisibleStats() {
        const stats = {
            total: 0,
            new: 0,
            in_progress: 0,
            paused: 0,
            completed: 0,
            cancelled: 0,
            transferred: 0,
            estimated_hours: 0,
            actual_hours: 0,
            total_points: 0
        };

        // Count visible tasks in table view
        const visibleRows = document.querySelectorAll('#myTasksTable tbody tr:not([style*="display: none"])');

        visibleRows.forEach(row => {
            const status = row.getAttribute('data-status');
            const isTransferred = row.getAttribute('data-is-transferred') === 'true';
            const points = parseInt(row.getAttribute('data-points') || 10);

            stats.total++;

            if (isTransferred) {
                stats.transferred++;
            } else if (stats.hasOwnProperty(status)) {
                stats[status]++;
            }

            stats.total_points += points;

            // Calculate time (would need to parse from data attributes if needed)
        });

        // Update the display
        updateStatsDisplay(stats);
    }

    /**
     * Update stats display in cards
     */
    function updateStatsDisplay(stats) {
        // Update values
        const statElements = {
            'stat-total': stats.total,
            'stat-new': stats.new,
            'stat-in-progress': stats.in_progress,
            'stat-paused': stats.paused,
            'stat-completed': stats.completed,
            'stat-transferred': stats.transferred,
            'stat-points': stats.total_points
        };

        Object.keys(statElements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                animateValue(element, parseInt(element.textContent), statElements[id], 500);
            }
        });

        // Update percentages
        updateStatsPercentages(stats);
    }

    /**
     * Update percentage displays
     */
    function updateStatsPercentages(stats) {
        // Completion percentage
        const completionPercentage = stats.total > 0 ? Math.round((stats.completed / stats.total) * 100) : 0;
        updateProgressBar('.completed-tasks', completionPercentage);

        // In progress percentage
        const inProgressPercentage = stats.total > 0 ? Math.round((stats.in_progress / stats.total) * 100) : 0;
        updateProgressBar('.in-progress-tasks', inProgressPercentage);

        // New tasks percentage
        const newPercentage = stats.total > 0 ? Math.round((stats.new / stats.total) * 100) : 0;
        updateProgressBar('.new-tasks', newPercentage);

        // Paused tasks percentage
        const pausedPercentage = stats.total > 0 ? Math.round((stats.paused / stats.total) * 100) : 0;
        updateProgressBar('.paused-tasks', pausedPercentage);

        // Transferred tasks percentage (if exists)
        if (stats.transferred > 0) {
            const transferredPercentage = stats.total > 0 ? Math.round((stats.transferred / stats.total) * 100) : 0;
            updateProgressBar('.transferred-tasks', transferredPercentage);
        }
    }

    /**
     * Update progress bar
     */
    function updateProgressBar(cardSelector, percentage) {
        const card = document.querySelector(cardSelector);
        if (!card) return;

        const progressFill = card.querySelector('.stats-progress-fill');
        const percentageSpan = card.querySelector('.stats-percentage');

        if (progressFill) {
            progressFill.style.width = percentage + '%';
        }

        if (percentageSpan) {
            percentageSpan.textContent = percentage + '%';
        }
    }

    /**
     * Animate number change
     */
    function animateValue(element, start, end, duration) {
        if (start === end) return;

        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;

            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }

            element.textContent = Math.round(current);
        }, 16);
    }

    /**
     * Listen to filter changes and update stats accordingly
     */
    function attachFilterListeners() {
        const filters = ['#projectFilter', '#statusFilter', '#searchInput', '#dateFrom', '#dateTo'];

        filters.forEach(selector => {
            const element = document.querySelector(selector);
            if (element) {
                element.addEventListener('change', () => {
                    setTimeout(updateVisibleStats, 100);
                });

                if (selector === '#searchInput') {
                    element.addEventListener('keyup', () => {
                        setTimeout(updateVisibleStats, 100);
                    });
                }
            }
        });
    }

    // Attach filter listeners on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachFilterListeners);
    } else {
        attachFilterListeners();
    }

    // Make functions available globally if needed
    window.myTasksStats = {
        updateVisibleStats: updateVisibleStats,
        applyStatsFilter: applyStatsFilter,
        clearStatsFilter: clearStatsFilter
    };

    console.log('📊 My Tasks Statistics Module Loaded');
})();

