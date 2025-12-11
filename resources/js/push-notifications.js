export class PushNotifications {
    constructor() {
        this.vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;
    }

    async register() {
        // Check for basic support
        if (!('serviceWorker' in navigator)) {
            console.error('Service workers not supported');
            window.dispatchEvent(new CustomEvent('push-notification-error', {
                detail: { message: 'Service workers are not supported in this browser' }
            }));
            return false;
        }

        if (!('PushManager' in window)) {
            console.error('Push API not supported');
            window.dispatchEvent(new CustomEvent('push-notification-error', {
                detail: { message: 'Push notifications are not supported in this browser' }
            }));
            return false;
        }

        if (!('Notification' in window)) {
            console.error('Notifications not supported');
            window.dispatchEvent(new CustomEvent('push-notification-error', {
                detail: { message: 'Notifications are not supported in this browser' }
            }));
            return false;
        }

        // Check if VAPID key is configured
        if (!this.vapidPublicKey) {
            console.error('VAPID public key not configured');
            window.dispatchEvent(new CustomEvent('push-notification-error', {
                detail: { message: 'Push notifications are not configured on this server' }
            }));
            return false;
        }

        try {
            // Register service worker
            const registration = await navigator.serviceWorker.register('/sw.js');

            // Request notification permission
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                console.log('Notification permission denied');
                window.dispatchEvent(new CustomEvent('push-notification-error', {
                    detail: { message: 'Notification permission was denied' }
                }));
                return false;
            }

            // Subscribe to push notifications
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });

            // Send subscription to server
            await this.saveSubscription(subscription);

            // Dispatch success event
            window.dispatchEvent(new CustomEvent('push-notification-registered', {
                detail: { message: 'Browser notifications enabled successfully!' }
            }));

            // Reload page to update UI after a brief delay
            setTimeout(() => window.location.reload(), 1000);

            return true;
        } catch (error) {
            console.error('Error registering push notifications:', error);
            window.dispatchEvent(new CustomEvent('push-notification-error', {
                detail: { message: error.message || 'Failed to enable browser notifications' }
            }));
            return false;
        }
    }

    async unregister() {
        try {
            const registration = await navigator.serviceWorker.getRegistration();
            if (!registration) return;

            const subscription = await registration.pushManager.getSubscription();
            if (subscription) {
                await subscription.unsubscribe();
                await this.deleteSubscription(subscription);
            }

            // Dispatch success event
            window.dispatchEvent(new CustomEvent('push-notification-unregistered', {
                detail: { message: 'Browser notifications disabled successfully!' }
            }));

            // Reload page to update UI after a brief delay
            setTimeout(() => window.location.reload(), 1000);
        } catch (error) {
            console.error('Error unregistering push notifications:', error);
            window.dispatchEvent(new CustomEvent('push-notification-error', {
                detail: { message: 'Failed to disable browser notifications' }
            }));
        }
    }

    async saveSubscription(subscription) {
        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(subscription)
        });

        if (!response.ok) {
            throw new Error('Failed to save subscription');
        }
    }

    async deleteSubscription(subscription) {
        await fetch('/api/push/unsubscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint
            })
        });
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
}

// Initialize on page load
window.pushNotifications = new PushNotifications();
