class NotificationSystem {
    constructor() {
        this.notificationContainer = null;
        this.initContainer();
    }

    initContainer() {
        this.notificationContainer = document.createElement('div');
        this.notificationContainer.className = 'notification-container';
        this.notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        `;
        document.body.appendChild(this.notificationContainer);
    }

    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.style.cssText = `
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 10px;
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="ph-fill ${this.getIcon(type)} me-2"></i>
                <div>${message}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        this.notificationContainer.appendChild(notification);

        // Auto dismiss after duration
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 150);
                }
            }, duration);
        }

        return notification;
    }

    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 5000) {
        return this.show(message, 'danger', duration);
    }

    warning(message, duration = 5000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }

    getIcon(type) {
        switch (type) {
            case 'success': return 'ph-check-circle';
            case 'danger': return 'ph-x-circle';
            case 'warning': return 'ph-warning';
            case 'info': return 'ph-info';
            default: return 'ph-info';
        }
    }
}

// Export singleton instance
window.notificationSystem = new NotificationSystem();