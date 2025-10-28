/**
 * ================================================
 * Services Analytics UI Enhancement
 * ملف تحسين واجهة المستخدم لتحليلات الخدمات
 * ================================================
 */

class ServicesAnalyticsUI {
    constructor() {
        this.init();
    }

    /**
     * Initialize UI enhancements
     */
    init() {
        this.setupAnimations();
        this.setupInteractiveElements();
        this.setupLoadingStates();
        this.setupProgressAnimations();
        this.setupChartAnimations();
        this.setupTooltips();
        this.setupMobileOptimizations();
    }

    /**
     * Setup entrance animations
     */
    setupAnimations() {
        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';

                    // Add staggered animation for card children
                    if (entry.target.classList.contains('row')) {
                        this.animateCardChildren(entry.target);
                    }
                }
            });
        }, observerOptions);

        // Observe elements with animation classes
        document.querySelectorAll('.fade-in, .slide-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });
    }

    /**
     * Animate card children with stagger effect
     */
    animateCardChildren(container) {
        const cards = container.querySelectorAll('.card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0) scale(1)';
            }, index * 100);
        });
    }

    /**
     * Setup interactive elements
     */
    setupInteractiveElements() {
        // Add hover effects to cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                this.addCardHoverEffect(card);
            });

            card.addEventListener('mouseleave', () => {
                this.removeCardHoverEffect(card);
            });
        });

        // Add click effects to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.addRippleEffect(e);
            });
        });

        // Add smooth scrolling to navigation
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    /**
     * Add card hover effect
     */
    addCardHoverEffect(card) {
        if (!card.classList.contains('bg-gradient-primary') &&
            !card.classList.contains('bg-gradient-success') &&
            !card.classList.contains('bg-gradient-info') &&
            !card.classList.contains('bg-gradient-warning')) {

            card.style.background = 'linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%)';
            card.style.borderColor = '#667eea';
        }
    }

    /**
     * Remove card hover effect
     */
    removeCardHoverEffect(card) {
        if (!card.classList.contains('bg-gradient-primary') &&
            !card.classList.contains('bg-gradient-success') &&
            !card.classList.contains('bg-gradient-info') &&
            !card.classList.contains('bg-gradient-warning')) {

            card.style.background = '';
            card.style.borderColor = '';
        }
    }

    /**
     * Add ripple effect to buttons
     */
    addRippleEffect(e) {
        const button = e.currentTarget;
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            left: ${x}px;
            top: ${y}px;
            width: ${size}px;
            height: ${size}px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
            z-index: 1;
        `;

        button.appendChild(ripple);

        // Remove ripple after animation
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    /**
     * Setup loading states
     */
    setupLoadingStates() {
        this.createLoadingOverlay();

        // Add loading state for data refresh
        document.addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-action') && e.target.getAttribute('data-action') === 'refresh') {
                this.showLoadingState(e.target.closest('[data-loading-target]'));
            }
        });
    }

    /**
     * Create loading overlay HTML
     */
    createLoadingOverlay() {
        const loadingHTML = `
            <div class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
        `;

        // Store for later use
        this.loadingOverlayHTML = loadingHTML;
    }

    /**
     * Show loading state
     */
    showLoadingState(element) {
        if (!element) return;

        const overlay = document.createElement('div');
        overlay.innerHTML = this.loadingOverlayHTML;
        overlay.classList.add('loading-overlay');

        element.style.position = 'relative';
        element.appendChild(overlay.firstElementChild);

        // Auto remove after 2 seconds (for demo)
        setTimeout(() => {
            this.hideLoadingState(element);
        }, 2000);
    }

    /**
     * Hide loading state
     */
    hideLoadingState(element) {
        if (!element) return;

        const overlay = element.querySelector('.loading-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.remove();
            }, 300);
        }
    }

    /**
     * Setup progress bar animations
     */
    setupProgressAnimations() {
        const progressBars = document.querySelectorAll('.progress-fill, .progress-fill-table');

        const progressObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const progressBar = entry.target;
                    const targetWidth = progressBar.style.width || progressBar.getAttribute('data-progress') + '%';

                    progressBar.style.width = '0%';
                    progressBar.style.transition = 'width 1.5s ease-out';

                    setTimeout(() => {
                        progressBar.style.width = targetWidth;
                    }, 100);

                    progressObserver.unobserve(progressBar);
                }
            });
        }, { threshold: 0.5 });

        progressBars.forEach(bar => {
            progressObserver.observe(bar);
        });
    }

    /**
     * Setup chart animations
     */
    setupChartAnimations() {
        // Enhanced chart options for Chart.js
        window.defaultChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            family: 'Cairo, sans-serif',
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#667eea',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true,
                    titleFont: {
                        family: 'Cairo, sans-serif',
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        family: 'Cairo, sans-serif',
                        size: 12
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        lineWidth: 1
                    },
                    ticks: {
                        font: {
                            family: 'Cairo, sans-serif'
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Cairo, sans-serif'
                        }
                    }
                }
            }
        };

        // Add gradient colors for charts
        window.createGradient = (ctx, color1, color2) => {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);
            return gradient;
        };
    }

    /**
     * Setup tooltips
     */
    setupTooltips() {
        // Initialize Bootstrap tooltips if available
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Custom tooltips for charts and progress bars
        document.querySelectorAll('.progress-container').forEach(container => {
            container.addEventListener('mouseenter', (e) => {
                this.showCustomTooltip(e, 'تفاصيل التقدم');
            });

            container.addEventListener('mouseleave', () => {
                this.hideCustomTooltip();
            });
        });
    }

    /**
     * Show custom tooltip
     */
    showCustomTooltip(e, text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;

        document.body.appendChild(tooltip);

        const updatePosition = (event) => {
            tooltip.style.left = event.pageX + 10 + 'px';
            tooltip.style.top = event.pageY - 30 + 'px';
        };

        updatePosition(e);
        setTimeout(() => tooltip.style.opacity = '1', 10);

        e.target.addEventListener('mousemove', updatePosition);
        this.currentTooltip = { element: tooltip, moveHandler: updatePosition, target: e.target };
    }

    /**
     * Hide custom tooltip
     */
    hideCustomTooltip() {
        if (this.currentTooltip) {
            this.currentTooltip.element.style.opacity = '0';
            this.currentTooltip.target.removeEventListener('mousemove', this.currentTooltip.moveHandler);

            setTimeout(() => {
                if (this.currentTooltip.element.parentNode) {
                    this.currentTooltip.element.remove();
                }
            }, 300);

            this.currentTooltip = null;
        }
    }

    /**
     * Setup mobile optimizations
     */
    setupMobileOptimizations() {
        // Touch gestures for mobile
        if ('ontouchstart' in window) {
            this.setupTouchGestures();
        }

        // Responsive table enhancements
        this.setupResponsiveTables();

        // Mobile menu enhancements
        this.setupMobileMenu();
    }

    /**
     * Setup touch gestures
     */
    setupTouchGestures() {
        let startX, startY, currentElement;

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            currentElement = e.target.closest('.card');
        });

        document.addEventListener('touchmove', (e) => {
            if (!currentElement) return;

            const currentX = e.touches[0].clientX;
            const currentY = e.touches[0].clientY;
            const diffX = startX - currentX;
            const diffY = startY - currentY;

            // Prevent default scroll if horizontal swipe
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 10) {
                e.preventDefault();
            }
        });
    }

    /**
     * Setup responsive tables
     */
    setupResponsiveTables() {
        const tables = document.querySelectorAll('.table-responsive');

        tables.forEach(table => {
            // Add horizontal scroll indicator
            const scrollIndicator = document.createElement('div');
            scrollIndicator.className = 'table-scroll-indicator';
            scrollIndicator.style.cssText = `
                position: absolute;
                right: 0;
                top: 50%;
                transform: translateY(-50%);
                background: linear-gradient(to left, rgba(0,0,0,0.1), transparent);
                width: 20px;
                height: 100%;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;

            table.style.position = 'relative';
            table.appendChild(scrollIndicator);

            // Show/hide scroll indicator
            table.addEventListener('scroll', () => {
                const maxScroll = table.scrollWidth - table.clientWidth;
                const currentScroll = table.scrollLeft;

                if (maxScroll > 0 && currentScroll < maxScroll - 10) {
                    scrollIndicator.style.opacity = '1';
                } else {
                    scrollIndicator.style.opacity = '0';
                }
            });
        });
    }

    /**
     * Setup mobile menu
     */
    setupMobileMenu() {
        // Enhanced mobile navigation
        const headerButtons = document.querySelectorAll('.header-section .btn');

        if (window.innerWidth <= 768 && headerButtons.length > 2) {
            this.createMobileMenu(headerButtons);
        }
    }

    /**
     * Create mobile menu
     */
    createMobileMenu(buttons) {
        const mobileMenu = document.createElement('div');
        mobileMenu.className = 'mobile-menu-dropdown';
        mobileMenu.style.cssText = `
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 10px;
            display: none;
            z-index: 1000;
            min-width: 200px;
        `;

        buttons.forEach((btn, index) => {
            if (index > 1) { // Keep first 2 buttons visible
                const menuItem = btn.cloneNode(true);
                menuItem.className = 'btn btn-sm btn-light d-block mb-2 w-100';
                mobileMenu.appendChild(menuItem);
                btn.style.display = 'none';
            }
        });

        // Add dropdown toggle
        const dropdownToggle = document.createElement('button');
        dropdownToggle.className = 'btn btn-light btn-sm';
        dropdownToggle.innerHTML = '<i class="fas fa-ellipsis-v"></i>';
        dropdownToggle.addEventListener('click', () => {
            mobileMenu.style.display = mobileMenu.style.display === 'none' ? 'block' : 'none';
        });

        const headerSection = document.querySelector('.header-section .d-flex.gap-2');
        headerSection.style.position = 'relative';
        headerSection.appendChild(dropdownToggle);
        headerSection.appendChild(mobileMenu);
    }

    /**
     * Add CSS animations to head
     */
    static addCustomStyles() {
        const styles = `
            <style>
                @keyframes ripple {
                    from {
                        transform: scale(0);
                        opacity: 1;
                    }
                    to {
                        transform: scale(2);
                        opacity: 0;
                    }
                }

                .custom-tooltip {
                    font-family: 'Cairo', sans-serif;
                    white-space: nowrap;
                }

                .mobile-menu-dropdown .btn {
                    transition: all 0.2s ease;
                }

                .mobile-menu-dropdown .btn:hover {
                    transform: translateX(-5px);
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                    color: white !important;
                }

                .table-scroll-indicator {
                    z-index: 10;
                }

                @media (max-width: 768px) {
                    .chart-container {
                        height: 250px !important;
                    }

                    .mobile-menu-dropdown {
                        animation: slideDown 0.3s ease;
                    }
                }

                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    /**
     * Update statistics with animation
     */
    updateStatistic(elementId, newValue, duration = 1500) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const startValue = parseInt(element.textContent) || 0;
        const endValue = parseInt(newValue);
        const increment = (endValue - startValue) / (duration / 16);
        let currentValue = startValue;

        const counter = setInterval(() => {
            currentValue += increment;

            if ((increment > 0 && currentValue >= endValue) ||
                (increment < 0 && currentValue <= endValue)) {
                currentValue = endValue;
                clearInterval(counter);
            }

            element.textContent = Math.round(currentValue);
        }, 16);
    }

    /**
     * Animate progress bar to value
     */
    animateProgressBar(progressElement, targetPercent, duration = 1000) {
        if (!progressElement) return;

        const startWidth = 0;
        const endWidth = targetPercent;
        const increment = (endWidth - startWidth) / (duration / 16);
        let currentWidth = startWidth;

        progressElement.style.width = '0%';

        const animation = setInterval(() => {
            currentWidth += increment;

            if (currentWidth >= endWidth) {
                currentWidth = endWidth;
                clearInterval(animation);
            }

            progressElement.style.width = currentWidth + '%';
        }, 16);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    ServicesAnalyticsUI.addCustomStyles();
    window.servicesAnalyticsUI = new ServicesAnalyticsUI();
});

// Export for global use
window.ServicesAnalyticsUI = ServicesAnalyticsUI;
