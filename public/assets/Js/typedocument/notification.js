class Notification {
    static show(message, type = 'info', duration = 5000) {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        // Icône selon le type
        let icon = 'mdi-information';
        switch(type) {
            case 'success':
                icon = 'mdi-check-circle';
                break;
            case 'error':
                icon = 'mdi-alert-circle';
                break;
            case 'warning':
                icon = 'mdi-alert';
                break;
        }
        
        // Contenu de la notification
        notification.innerHTML = `
            <i class="mdi ${icon} icon"></i>
            <div class="content">${message}</div>
            <button class="close">&times;</button>
        `;
        
        // Ajouter au conteneur
        const container = document.getElementById('notification-container');
        if (!container) {
            // Créer le conteneur s'il n'existe pas
            const newContainer = document.createElement('div');
            newContainer.id = 'notification-container';
            document.body.appendChild(newContainer);
            newContainer.appendChild(notification);
        } else {
            container.appendChild(notification);
        }
        
        // Fermeture au clic sur le bouton
        const closeBtn = notification.querySelector('.close');
        closeBtn.addEventListener('click', () => {
            this.removeNotification(notification);
        });
        
        // Fermeture automatique après la durée spécifiée
        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);
        }
        
        return notification;
    }

    static removeNotification(notification) {
        notification.style.animation = 'slideOut 0.3s forwards';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    static success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    static error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }

    static warning(message, duration = 5000) {
        return this.show(message, 'warning', duration);
    }

    static info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }
}

window.Notification = Notification;