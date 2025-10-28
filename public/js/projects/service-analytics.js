
class ServiceAnalytics {
    constructor(projectId) {
        this.projectId = projectId;
        this.charts = {};
        this.servicesData = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadServiceData();
    }

    bindEvents() {
    
        const progressSlider = document.getElementById('progressPercentage');
        if (progressSlider) {
            progressSlider.addEventListener('input', (e) => {
                document.getElementById('progressValue').textContent = e.target.value + '%';
            });
        }


        const statusSelect = document.getElementById('serviceStatus');
        if (statusSelect) {
            statusSelect.addEventListener('change', (e) => {
                this.autoUpdateProgress(e.target.value);
            });
        }

        // Refresh button
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="refresh"]')) {
                this.loadServiceData();
            }
        });
    }

    autoUpdateProgress(status) {
        const progressSlider = document.getElementById('progressPercentage');
        if (!progressSlider) return;

        const statusProgress = {
            'لم تبدأ': 0,
            'قيد التنفيذ': 50,
            'مكتملة': 100,
            'معلقة': 25,
            'ملغية': 0
        };

        const progress = statusProgress[status] || 0;
        progressSlider.value = progress;
        document.getElementById('progressValue').textContent = progress + '%';
    }

    async loadServiceData() {
        try {
            this.showLoading();
            const response = await this.fetchServiceAnalytics();

            if (response.ok) {
                const data = await response.json();
                this.servicesData = data;
                this.updateUI(data);
            } else {
                const error = await response.json();
                this.showError(error.error || 'حدث خطأ أثناء تحميل البيانات');
            }
        } catch (error) {
            console.error('Error loading service data:', error);
            this.showError('حدث خطأ أثناء تحميل البيانات');
        } finally {
            this.hideLoading();
        }
    }

    async fetchServiceAnalytics() {
        return fetch(`/projects/${this.projectId}/service-analytics-data`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
    }

    updateUI(data) {
        this.updateOverviewCards(data);
        this.renderCharts(data);
        this.renderServicesTable(data.services);
        this.renderDepartmentProgress(data.department_progress);
    }

    updateOverviewCards(data) {
        const updates = {
            'total-services': data.total_services,
            'completed-services': data.status_counts['مكتملة'] || 0,
            'completion-rate': data.completion_rate,
            'points-rate': data.points_completion_rate
        };

        Object.entries(updates).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                this.animateValue(element, value);
            }
        });
    }

    animateValue(element, endValue) {
        const startValue = parseInt(element.textContent) || 0;
        const duration = 1000; // 1 second
        const startTime = performance.now();

        const updateValue = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function
            const easeOutCubic = 1 - Math.pow(1 - progress, 3);

            const currentValue = Math.round(startValue + (endValue - startValue) * easeOutCubic);
            element.textContent = currentValue;

            if (progress < 1) {
                requestAnimationFrame(updateValue);
            }
        };

        requestAnimationFrame(updateValue);
    }

    renderCharts(data) {
        this.renderServiceStatusChart(data.chart_data.status_pie);
        this.renderDepartmentChart(data.chart_data.department_bar);
        this.renderServiceTimelineChart(data.service_timeline);
    }

    renderServiceStatusChart(chartData) {
        const ctx = document.getElementById('service-status-chart');
        if (!ctx) return;

        // Destroy existing chart
        if (this.charts.statusChart) {
            this.charts.statusChart.destroy();
        }

        this.charts.statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.data,
                    backgroundColor: chartData.colors,
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 1000
                }
            }
        });
    }

    renderDepartmentChart(chartData) {
        const ctx = document.getElementById('department-progress-chart');
        if (!ctx) return;

        if (this.charts.departmentChart) {
            this.charts.departmentChart.destroy();
        }

        this.charts.departmentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'معدل الإكمال (%)',
                    data: chartData.completion_rates,
                    backgroundColor: '#3B82F6',
                    borderColor: '#1D4ED8',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            },
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `معدل الإكمال: ${context.parsed.y}%`;
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutBounce'
                }
            }
        });
    }

    renderServiceTimelineChart(timelineData) {
        const ctx = document.getElementById('service-timeline-chart');
        if (!ctx || !timelineData.length) return;

        if (this.charts.timelineChart) {
            this.charts.timelineChart.destroy();
        }

        // Process timeline data for chart
        const labels = timelineData.map(item =>
            new Date(item.created_at).toLocaleDateString('ar-SA', { month: 'short', day: 'numeric' })
        );
        const completedData = [];
        const inProgressData = [];
        const notStartedData = [];

        timelineData.forEach((item, index) => {
            switch(item.status) {
                case 'مكتملة':
                    completedData.push(index + 1);
                    inProgressData.push(null);
                    notStartedData.push(null);
                    break;
                case 'قيد التنفيذ':
                    completedData.push(null);
                    inProgressData.push(index + 1);
                    notStartedData.push(null);
                    break;
                default:
                    completedData.push(null);
                    inProgressData.push(null);
                    notStartedData.push(index + 1);
            }
        });

        this.charts.timelineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'مكتملة',
                        data: completedData,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        fill: false,
                        tension: 0.4
                    },
                    {
                        label: 'قيد التنفيذ',
                        data: inProgressData,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        pointBackgroundColor: '#007bff',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        fill: false,
                        tension: 0.4
                    },
                    {
                        label: 'لم تبدأ',
                        data: notStartedData,
                        borderColor: '#6c757d',
                        backgroundColor: 'rgba(108, 117, 125, 0.1)',
                        pointBackgroundColor: '#6c757d',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    renderServicesTable(services) {
        const tbody = document.querySelector('#services-table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        services.forEach((service, index) => {
            const row = this.createServiceRow(service, index);
            tbody.appendChild(row);
        });

        // Add animation to rows
        tbody.querySelectorAll('tr').forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(10px)';

            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 50);
        });
    }

    createServiceRow(service, index) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="fw-medium">${service.name}</div>
                <small class="text-muted">القسم: ${service.department}</small>
            </td>
            <td>
                <span class="status-badge status-${this.getStatusClass(service.status)}">${service.status}</span>
            </td>
            <td>
                <div class="service-progress-bar mb-1">
                    <div class="service-progress-fill progress-${this.getProgressClass(service.progress_percentage)}"
                         style="width: ${service.progress_percentage}%"></div>
                </div>
                <small class="text-muted">${service.progress_percentage}%</small>
            </td>
            <td>
                <div class="participants-avatars">
                    ${service.participants.slice(0, 3).map(p =>
                        `<div class="participant-avatar" title="${p.name}">${p.name.charAt(0)}</div>`
                    ).join('')}
                    ${service.participants.length > 3 ?
                        `<div class="participant-avatar">+${service.participants.length - 3}</div>` : ''
                    }
                </div>
                <small class="text-muted d-block">${service.participants_count} مشارك</small>
            </td>
            <td>
                <div class="text-center">
                    <div class="fw-medium">${service.task_completion.completion_rate}%</div>
                    <small class="text-muted">${service.task_completion.completed_tasks}/${service.task_completion.total_tasks}</small>
                </div>
            </td>
            <td>
                <span class="badge bg-primary">${service.points} نقطة</span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary"
                            onclick="serviceAnalytics.openProgressModal(${service.id}, '${service.name}', '${service.status}', ${service.progress_percentage})"
                            title="تحديث التقدم">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-info"
                            onclick="serviceAnalytics.viewServiceHistory(${service.id})"
                            title="عرض التاريخ">
                        <i class="fas fa-history"></i>
                    </button>
                </div>
            </td>
        `;
        return row;
    }

    getStatusClass(status) {
        const statusMap = {
            'لم تبدأ': 'not-started',
            'قيد التنفيذ': 'in-progress',
            'مكتملة': 'completed',
            'معلقة': 'paused',
            'ملغية': 'cancelled'
        };
        return statusMap[status] || 'not-started';
    }

    getProgressClass(progress) {
        if (progress === 0) return '0';
        if (progress <= 25) return '25';
        if (progress <= 50) return '50';
        if (progress <= 75) return '75';
        return '100';
    }

    renderDepartmentProgress(departmentProgress) {
        const container = document.getElementById('department-progress-container');
        if (!container) return;

        container.innerHTML = '';

        Object.entries(departmentProgress).forEach(([department, progress]) => {
            const item = document.createElement('div');
            item.className = 'department-item slide-in';
            item.innerHTML = `
                <div class="department-name">${department}</div>
                <div class="department-progress-bar">
                    <div class="department-progress-fill" style="width: ${progress.completion_rate}%"></div>
                </div>
                <div class="department-percentage">${progress.completion_rate}%</div>
            `;
            container.appendChild(item);
        });
    }

    async openProgressModal(serviceId, serviceName, currentStatus, currentProgress) {
        const modal = document.getElementById('serviceProgressModal');
        if (!modal) return;

        // Set form values
        document.getElementById('serviceId').value = serviceId;
        document.getElementById('serviceStatus').value = currentStatus;
        document.getElementById('progressPercentage').value = currentProgress;
        document.getElementById('progressValue').textContent = currentProgress + '%';

        // Show modal
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }

    async updateServiceProgress() {
        const form = document.getElementById('serviceProgressForm');
        const formData = new FormData(form);
        const serviceId = document.getElementById('serviceId').value;

        try {
            this.showLoading();

            const response = await fetch(`/projects/${this.projectId}/services/${serviceId}/progress`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    status: formData.get('status'),
                    progress_percentage: formData.get('progress_percentage'),
                    progress_notes: formData.get('progress_notes')
                })
            });

            const result = await response.json();

            if (response.ok) {
                bootstrap.Modal.getInstance(document.getElementById('serviceProgressModal')).hide();
                this.loadServiceData(); // Refresh data
                this.showSuccess(result.message);
            } else {
                this.showError(result.error || 'حدث خطأ أثناء التحديث');
            }
        } catch (error) {
            console.error('Error updating service progress:', error);
            this.showError('حدث خطأ أثناء التحديث');
        } finally {
            this.hideLoading();
        }
    }

    async viewServiceHistory(serviceId) {
        try {
            const response = await fetch(`/projects/${this.projectId}/services/${serviceId}/history`);
            const data = await response.json();

            if (response.ok) {
                this.renderServiceHistory(data);
                const modal = new bootstrap.Modal(document.getElementById('serviceHistoryModal'));
                modal.show();
            } else {
                this.showError(data.error || 'حدث خطأ أثناء تحميل التاريخ');
            }
        } catch (error) {
            console.error('Error loading service history:', error);
            this.showError('حدث خطأ أثناء تحميل التاريخ');
        }
    }

    renderServiceHistory(data) {
        const content = document.getElementById('service-history-content');
        if (!content) return;

        let html = `
            <div class="mb-3">
                <h6>${data.service_name}</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted mb-1">الحالة الحالية:</p>
                        <span class="status-badge status-${this.getStatusClass(data.current_status)}">${data.current_status}</span>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted mb-1">التقدم الحالي:</p>
                        <strong>${data.current_progress}%</strong>
                    </div>
                </div>
            </div>
            <div class="timeline">
        `;

        if (data.history && data.history.length > 0) {
            data.history.forEach((entry, index) => {
                html += `
                    <div class="timeline-item fade-in" style="animation-delay: ${index * 0.1}s">
                        <div class="timeline-date">
                            ${new Date(entry.updated_at).toLocaleDateString('ar-SA', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}
                        </div>
                        <div class="timeline-content">
                            <div class="fw-medium">${entry.status} - ${entry.progress_percentage}%</div>
                            <div class="text-muted">بواسطة: ${entry.updated_by}</div>
                            ${entry.notes ? `<div class="mt-1 text-dark">${entry.notes}</div>` : ''}
                        </div>
                    </div>
                `;
            });
        } else {
            html += '<p class="text-muted">لا يوجد تاريخ متاح لهذه الخدمة</p>';
        }

        html += '</div>';
        content.innerHTML = html;
    }

    showLoading() {
        const elements = document.querySelectorAll('[data-loading-target]');
        elements.forEach(el => el.classList.add('loading'));
    }

    hideLoading() {
        const elements = document.querySelectorAll('[data-loading-target]');
        elements.forEach(el => el.classList.remove('loading'));
    }

    showSuccess(message) {
        this.showAlert('success', message);
    }

    showError(message) {
        this.showAlert('error', message);
    }

    showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;">
                <i class="${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', alertHtml);

        // Auto-remove after 4 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert.show');
            alerts.forEach(alert => {
                if (alert.classList.contains('fade')) {
                    alert.classList.remove('show');
                    setTimeout(() => alert.remove(), 300);
                } else {
                    alert.remove();
                }
            });
        }, 4000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const projectId = document.querySelector('[data-project-id]')?.getAttribute('data-project-id');
    if (projectId) {
        window.serviceAnalytics = new ServiceAnalytics(projectId);
    }
});

// Export for global use
window.ServiceAnalytics = ServiceAnalytics;
