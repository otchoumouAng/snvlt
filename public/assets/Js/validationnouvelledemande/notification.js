// Simple notification system using a library like Toastify or just a custom div
class NotificationSystem {
    constructor() {
        // In a real app, you would initialize a library here.
        // For this example, we'll just use console logs.
    }

    success(message) {
        console.log(`%cSUCCESS: ${message}`, 'color: green; font-weight: bold;');
        // In a real app: toastify.success(message);
    }

    error(message) {
        console.error(`ERROR: ${message}`);
        // In a real app: toastify.error(message);
    }

    info(message) {
        console.log(`%cINFO: ${message}`, 'color: blue;');
        // In a real app: toastify.info(message);
    }

    warning(message) {
        console.warn(`WARNING: ${message}`);
        // In a real app: toastify.warn(message);
    }
}

window.notificationSystem = new NotificationSystem();
