class Notification {
    static show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;

        let icon = 'mdi-information';
        switch(type) {
            case 'success': icon = 'mdi-check-circle'; break;
            case 'error': icon = 'mdi-alert-circle'; break;
            case 'warning': icon = 'mdi-alert'; break;
        }

        notification.innerHTML = `
            <i class="mdi ${icon} icon"></i>
            <div class="content">${message}</div>
            <button class="close">&times;</button>
        `;

        const container = document.getElementById('notification-container');
        if (container) {
            container.appendChild(notification);
        } else {
            const newContainer = document.createElement('div');
            newContainer.id = 'notification-container';
            document.body.appendChild(newContainer);
            newContainer.appendChild(notification);
        }

        notification.querySelector('.close').addEventListener('click', () => this.removeNotification(notification));

        if (duration > 0) {
            setTimeout(() => this.removeNotification(notification), duration);
        }
    }

    static removeNotification(notification) {
        notification.style.animation = 'slideOut 0.3s forwards';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    static success(message, duration = 5000) { this.show(message, 'success', duration); }
    static error(message, duration = 5000) { this.show(message, 'error', duration); }
    static warning(message, duration = 5000) { this.show(message, 'warning', duration); }
    static info(message, duration = 5000) { this.show(message, 'info', duration); }
}

window.Notification = Notification;
