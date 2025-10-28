class ToastManager {
    constructor() {
        this.init();
    }

    init() {
        if (!document.getElementById('toast-container')) {
            this.createContainer();
        }
    }

    createContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    show(message, type = 'success', duration = 20000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icon = this.getIcon(type);
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${icon}</span>
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        const container = document.getElementById('toast-container');
        container.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);

        if (duration > 0) {
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }
            }, duration);
        }
    }

    getIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }

    success(message, duration = 20000) {
        this.show(message, 'success', duration);
    }

    error(message, duration = 20000) {
        this.show(message, 'error', duration);
    }

    warning(message, duration = 20000) {
        this.show(message, 'warning', duration);
    }

    info(message, duration = 20000) {
        this.show(message, 'info', duration);
    }
}

const Toast = new ToastManager();

function showSlackNotificationResult(success, context = 'Slack') {
    if (success) {
        Toast.success(`تم إرسال إشعار ${context} بنجاح`);
    } else {
        Toast.error(`فشل في إرسال إشعار ${context}`);
    }
}

window.Toast = Toast;
window.showSlackNotificationResult = showSlackNotificationResult;
