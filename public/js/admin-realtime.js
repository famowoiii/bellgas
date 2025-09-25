// Real-time functionality for Admin Dashboard
class AdminRealtime {
    constructor() {
        this.pollingInterval = null;
        this.lastOrderCount = 0;
        this.notifications = [];
        this.soundEnabled = true;
        this.autoRefreshEnabled = true;
        this.onUpdateCallback = null;

        this.loadSettings();
    }

    init(onUpdateCallback) {
        this.onUpdateCallback = onUpdateCallback;
        this.startPolling();
    }

    startPolling() {
        if (this.autoRefreshEnabled && !this.pollingInterval) {
            this.pollingInterval = setInterval(() => {
                this.checkForUpdates();
            }, 30000); // Poll every 30 seconds
            console.log('📡 Real-time polling started (30s interval)');
            this.showToast('Real-time monitoring active', 'info');
        }
    }

    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            console.log('📡 Real-time polling stopped');
        }
    }

    async checkForUpdates() {
        try {
            const response = await axios.get('/api/realtime/admin-stats');
            if (response.data.success) {
                const newStats = response.data.data;

                // Check for new orders
                if (newStats.total_orders > this.lastOrderCount) {
                    const newOrdersCount = newStats.total_orders - this.lastOrderCount;
                    this.handleNewOrders(newOrdersCount);
                }

                this.lastOrderCount = newStats.total_orders;

                // Notify parent component of updates
                if (this.onUpdateCallback) {
                    this.onUpdateCallback(newStats);
                }
            }
        } catch (error) {
            console.error('❌ Real-time update failed:', error);
        }
    }

    handleNewOrders(count) {
        const message = count === 1 ? '🔔 New order received!' : `🔔 ${count} new orders received!`;
        this.showToast(message, 'success', true);
        this.playNotificationSound();

        // Add to notifications list
        this.addNotification(message, 'success', true);
    }

    playNotificationSound() {
        if (!this.soundEnabled) return;

        try {
            // Create a pleasant notification sound
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();

            // First tone (higher)
            const oscillator1 = audioContext.createOscillator();
            const gainNode1 = audioContext.createGain();
            oscillator1.connect(gainNode1);
            gainNode1.connect(audioContext.destination);
            oscillator1.frequency.setValueAtTime(800, audioContext.currentTime);
            gainNode1.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode1.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            oscillator1.start();
            oscillator1.stop(audioContext.currentTime + 0.1);

            // Second tone (lower)
            setTimeout(() => {
                const oscillator2 = audioContext.createOscillator();
                const gainNode2 = audioContext.createGain();
                oscillator2.connect(gainNode2);
                gainNode2.connect(audioContext.destination);
                oscillator2.frequency.setValueAtTime(600, audioContext.currentTime);
                gainNode2.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
                oscillator2.start();
                oscillator2.stop(audioContext.currentTime + 0.1);
            }, 100);

        } catch (error) {
            console.warn('Could not play notification sound:', error);
        }
    }

    addNotification(message, type, persistent = false) {
        const notification = {
            id: Date.now() + Math.random(),
            message: message,
            type: type,
            timestamp: new Date(),
            persistent: persistent
        };

        this.notifications.unshift(notification);

        // Keep only last 50 notifications
        if (this.notifications.length > 50) {
            this.notifications = this.notifications.slice(0, 50);
        }

        // Auto-remove non-persistent notifications
        if (!persistent) {
            setTimeout(() => {
                this.removeNotification(notification.id);
            }, 5000);
        }
    }

    removeNotification(id) {
        this.notifications = this.notifications.filter(n => n.id !== id);
    }

    clearNotifications() {
        this.notifications = [];
    }

    showToast(message, type = 'info', autoHide = true) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg border-l-4 max-w-sm transform transition-transform duration-300 translate-x-full ${this.getToastClasses(type)}`;
        toast.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    ${this.getToastIcon(type)}
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <div class="ml-4">
                    <button class="text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);

        // Auto-hide
        if (autoHide) {
            setTimeout(() => {
                toast.style.transform = 'translateX(full)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
    }

    getToastClasses(type) {
        const classes = {
            'success': 'bg-green-50 border-green-400 text-green-800',
            'error': 'bg-red-50 border-red-400 text-red-800',
            'warning': 'bg-yellow-50 border-yellow-400 text-yellow-800',
            'info': 'bg-blue-50 border-blue-400 text-blue-800'
        };
        return classes[type] || classes.info;
    }

    getToastIcon(type) {
        const icons = {
            'success': '<i class="fas fa-check-circle text-green-400"></i>',
            'error': '<i class="fas fa-exclamation-circle text-red-400"></i>',
            'warning': '<i class="fas fa-exclamation-triangle text-yellow-400"></i>',
            'info': '<i class="fas fa-info-circle text-blue-400"></i>'
        };
        return icons[type] || icons.info;
    }

    toggleAutoRefresh() {
        this.autoRefreshEnabled = !this.autoRefreshEnabled;
        this.saveSettings();

        if (this.autoRefreshEnabled) {
            this.startPolling();
            this.showToast('Auto-refresh enabled', 'info');
        } else {
            this.stopPolling();
            this.showToast('Auto-refresh disabled', 'info');
        }

        return this.autoRefreshEnabled;
    }

    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        this.saveSettings();

        const message = this.soundEnabled ? 'Sound notifications enabled' : 'Sound notifications disabled';
        this.showToast(message, 'info');

        // Test sound if enabled
        if (this.soundEnabled) {
            setTimeout(() => this.playNotificationSound(), 500);
        }

        return this.soundEnabled;
    }

    loadSettings() {
        const saved = localStorage.getItem('admin_realtime_settings');
        if (saved) {
            const settings = JSON.parse(saved);
            this.soundEnabled = settings.soundEnabled !== false;
            this.autoRefreshEnabled = settings.autoRefreshEnabled !== false;
        }
    }

    saveSettings() {
        const settings = {
            soundEnabled: this.soundEnabled,
            autoRefreshEnabled: this.autoRefreshEnabled
        };
        localStorage.setItem('admin_realtime_settings', JSON.stringify(settings));
    }

    getSettings() {
        return {
            soundEnabled: this.soundEnabled,
            autoRefreshEnabled: this.autoRefreshEnabled,
            notificationCount: this.notifications.length
        };
    }

    destroy() {
        this.stopPolling();
    }
}

// Global instance
window.AdminRealtime = new AdminRealtime();