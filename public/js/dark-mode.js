/**
 * Dark Mode Toggle Functionality for Arkan Dashboard
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get the dark mode toggle button
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (!darkModeToggle) {
        console.log('Dark mode toggle button not found!');
        return;
    }

    // Reference to HTML and body elements
    const htmlElement = document.documentElement;
    const bodyElement = document.body;

    // Check for saved dark mode preference
    const savedDarkMode = localStorage.getItem('darkMode');

    // Function to enable dark mode
    function enableDarkMode() {
        console.log('Enabling dark mode');
        htmlElement.classList.add('dark-mode');
        bodyElement.classList.add('dark-mode');
        darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        darkModeToggle.title = 'تبديل الوضع المضيء';
        localStorage.setItem('darkMode', 'enabled');

        // Force update all elements that might have white backgrounds
        forceUpdateElementsForDarkMode(true);

        // Update chart colors and options if charts exist
        updateChartsForDarkMode(true);
    }

    // Function to disable dark mode
    function disableDarkMode() {
        console.log('Disabling dark mode');
        htmlElement.classList.remove('dark-mode');
        bodyElement.classList.remove('dark-mode');
        darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        darkModeToggle.title = 'تبديل الوضع المظلم';
        localStorage.setItem('darkMode', 'disabled');

        // Force update all elements back to light mode
        forceUpdateElementsForDarkMode(false);

        // Update chart colors and options if charts exist
        updateChartsForDarkMode(false);
    }

    // Function to update charts for dark mode
    function updateChartsForDarkMode(isDarkMode) {
        // If Chart.js is loaded and charts exist
        if (typeof Chart !== 'undefined') {
            // Get all canvas elements that might contain charts
            const chartElements = document.querySelectorAll('canvas');

            // Update global Chart.js defaults
            Chart.defaults.color = isDarkMode ? '#e9ecef' : '#2c3e50';
            Chart.defaults.borderColor = isDarkMode ? '#2d3748' : '#e9ecef';

            // Loop through all potential chart canvases
            chartElements.forEach(canvas => {
                const chartInstance = Chart.getChart(canvas);
                if (chartInstance) {
                    // Update legend text color
                    if (chartInstance.options.plugins && chartInstance.options.plugins.legend) {
                        chartInstance.options.plugins.legend.labels = {
                            color: isDarkMode ? '#e9ecef' : '#2c3e50',
                            font: {
                                weight: 'bold'
                            }
                        };
                    }

                    // Update the chart to reflect the changes
                    chartInstance.update();
                }
            });
        }

        // Update Kanban board colors if it exists
        updateKanbanBoardForDarkMode(isDarkMode);
    }

    // Function to update Kanban board for dark mode
    function updateKanbanBoardForDarkMode(isDarkMode) {
        // Find the Kanban board container
        const kanbanBoard = document.querySelector('.kanban-board');
        if (!kanbanBoard) return;

        // Update Kanban cards for better contrast in different modes
        const kanbanCards = document.querySelectorAll('.kanban-card');
        kanbanCards.forEach(card => {
            if (isDarkMode) {
                card.style.borderColor = getComputedStyle(card).borderRightColor;
                card.style.boxShadow = 'var(--shadow-sm)';
            } else {
                card.style.borderColor = '';
                card.style.boxShadow = '';
            }
        });

        // Update progress bars for better visibility
        const progressBars = document.querySelectorAll('.kanban-card .progress-bar');
        progressBars.forEach(bar => {
            if (isDarkMode) {
                bar.style.opacity = '0.9';
            } else {
                bar.style.opacity = '';
            }
        });
    }

    // Function to force update elements for dark mode
    function forceUpdateElementsForDarkMode(isDarkMode) {
        // Get all elements that might have white backgrounds
        const elementsToUpdate = [
            '.kanban-card',
            '.project-card',
            '.meeting-card',
            '.employee-card',
            '.service-card',
            '.stat-card',
            '.chart-container',
            '.table-container',
            '.card',
            '.modal-content',
            '.dropdown-menu',
            '.form-control',
            '.form-select',
            '.input-group-text',
            '.alert',
            '.list-group-item',
            '.nav-tabs .nav-link'
        ];

        elementsToUpdate.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                // Skip navbar and footer elements
                if (element.closest('.navbar') || element.closest('footer') || element.closest('nav')) {
                    return;
                }

                if (isDarkMode) {
                    // Force dark mode styles
                    element.style.setProperty('background-color', 'rgba(26, 26, 46, 0.95)', 'important');
                    element.style.setProperty('color', '#ffffff', 'important');
                    element.style.setProperty('border-color', 'rgba(255, 255, 255, 0.1)', 'important');
                } else {
                    // Remove forced styles to return to light mode
                    element.style.removeProperty('background-color');
                    element.style.removeProperty('color');
                    element.style.removeProperty('border-color');
                }
            });
        });

        // Special handling for text elements
        const textElements = document.querySelectorAll('.text-dark, .text-muted');
        textElements.forEach(element => {
            if (isDarkMode) {
                if (element.classList.contains('text-dark')) {
                    element.style.setProperty('color', '#ffffff', 'important');
                } else if (element.classList.contains('text-muted')) {
                    element.style.setProperty('color', '#b8bcc8', 'important');
                }
            } else {
                element.style.removeProperty('color');
            }
        });

        // Force update for any remaining white backgrounds
        if (isDarkMode) {
            const whiteElements = document.querySelectorAll('[style*="background-color: white"], [style*="background-color: #ffffff"], [style*="background: white"], [style*="background: #ffffff"]');
            whiteElements.forEach(element => {
                // Skip navbar and footer elements
                if (element.closest('.navbar') || element.closest('footer') || element.closest('nav')) {
                    return;
                }
                element.style.setProperty('background-color', 'rgba(26, 26, 46, 0.95)', 'important');
                element.style.setProperty('color', '#ffffff', 'important');
            });
        }

        // Special handling for tables
        const tables = document.querySelectorAll('table, .table, .table-responsive');
        tables.forEach(table => {
            // Skip navbar and footer tables
            if (table.closest('.navbar') || table.closest('footer') || table.closest('nav')) {
                return;
            }

            if (isDarkMode) {
                table.style.setProperty('background-color', 'transparent', 'important');
                table.style.setProperty('color', '#ffffff', 'important');

                // Fix table cells
                const cells = table.querySelectorAll('td, th, tbody, thead, tr');
                cells.forEach(cell => {
                    cell.style.setProperty('background-color', 'transparent', 'important');
                    cell.style.setProperty('color', '#ffffff', 'important');
                    cell.style.setProperty('border-color', 'rgba(255, 255, 255, 0.1)', 'important');
                });
            } else {
                table.style.removeProperty('background-color');
                table.style.removeProperty('color');

                const cells = table.querySelectorAll('td, th, tbody, thead, tr');
                cells.forEach(cell => {
                    cell.style.removeProperty('background-color');
                    cell.style.removeProperty('color');
                    cell.style.removeProperty('border-color');
                });
            }
        });
    }

    // Apply dark mode if saved
    if (savedDarkMode === 'enabled') {
        enableDarkMode();
    }

    // Dark mode toggle button click handler
    darkModeToggle.addEventListener('click', function() {
        console.log('Dark mode toggle clicked');
        if (htmlElement.classList.contains('dark-mode')) {
            disableDarkMode();
        } else {
            enableDarkMode();
        }
    });

    // Observer to watch for dynamically added elements
    const observer = new MutationObserver(function(mutations) {
        if (htmlElement.classList.contains('dark-mode')) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Apply dark mode styles to newly added elements
                        setTimeout(() => {
                            forceUpdateElementsForDarkMode(true);
                        }, 100);
                    }
                });
            });
        }
    });

    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Additional check on window load to ensure all elements are properly styled
    window.addEventListener('load', function() {
        if (htmlElement.classList.contains('dark-mode')) {
            setTimeout(() => {
                forceUpdateElementsForDarkMode(true);
            }, 500);
        }
    });
});
