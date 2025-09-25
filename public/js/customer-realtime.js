// Real-time functionality for Customer Order Tracking
class CustomerRealtime {
    constructor() {
        this.pollingInterval = null;
        this.orderId = null;
        this.lastStatus = null;
        this.onUpdateCallback = null;
        this.notifications = [];
        this.soundEnabled = true;

        this.loadSettings();
    }

    init(orderId, onUpdateCallback) {
        this.orderId = orderId;
        this.onUpdateCallback = onUpdateCallback;
        this.startPolling();
    }

    startPolling() {
        if (this.orderId && !this.pollingInterval) {
            this.pollingInterval = setInterval(() => {
                this.checkOrderStatus();
            }, 15000); // Poll every 15 seconds for customers
            console.log('📱 Customer order tracking started (15s interval)');
        }
    }

    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            console.log('📱 Customer order tracking stopped');
        }
    }

    async checkOrderStatus() {
        try {
            const response = await axios.get(`/api/realtime/customer-orders?order_id=${this.orderId}`);
            if (response.data.success && response.data.data.length > 0) {
                const orderData = response.data.data[0];

                // Check for status change
                if (this.lastStatus && this.lastStatus !== orderData.status) {
                    this.handleStatusChange(this.lastStatus, orderData.status);
                }

                this.lastStatus = orderData.status;

                // Notify parent component
                if (this.onUpdateCallback) {
                    this.onUpdateCallback(orderData);
                }
            }
        } catch (error) {
            console.error('❌ Failed to check order status:', error);
        }
    }

    handleStatusChange(oldStatus, newStatus) {
        const statusMessages = {
            'PAID': {
                message: '✅ Payment confirmed! Your order is now being processed.',
                type: 'success'
            },
            'PROCESSED': {
                message: '📦 Great news! Your order has been processed and is being prepared.',
                type: 'info'
            },
            'ON_DELIVERY': {
                message: '🚛 Your order is on the way! Our driver will contact you shortly.',
                type: 'info'
            },
            'WAITING_PICKUP': {
                message: '🏪 Your order is ready for pickup! Please come to our store.',
                type: 'success'
            },
            'DONE': {
                message: '🎉 Order completed! Thank you for choosing BellGas.',
                type: 'success'
            },
            'CANCELLED': {
                message: '❌ Your order has been cancelled. Please contact us if you have questions.',
                type: 'error'
            }
        };

        const statusInfo = statusMessages[newStatus];
        if (statusInfo) {
            this.showToast(statusInfo.message, statusInfo.type);
            this.playNotificationSound();
            this.addNotification(statusInfo.message, statusInfo.type);
        }
    }

    playNotificationSound() {
        if (!this.soundEnabled) return;

        try {
            // Create a gentle notification sound for customers
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();

            // Pleasant ascending chime
            const frequencies = [523.25, 659.25, 783.99]; // C5, E5, G5

            frequencies.forEach((freq, index) => {
                setTimeout(() => {
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();

                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);

                    oscillator.frequency.setValueAtTime(freq, audioContext.currentTime);
                    gainNode.gain.setValueAtTime(0.15, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.3);
                }, index * 150);
            });

        } catch (error) {
            console.warn('Could not play notification sound:', error);
        }
    }

    addNotification(message, type, persistent = true) {
        const notification = {
            id: Date.now() + Math.random(),
            message: message,
            type: type,
            timestamp: new Date(),
            persistent: persistent
        };

        this.notifications.unshift(notification);

        // Keep only last 20 notifications
        if (this.notifications.length > 20) {
            this.notifications = this.notifications.slice(0, 20);
        }
    }

    showToast(message, type = 'info', duration = 8000) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 p-6 rounded-lg shadow-xl border-l-4 max-w-md transform transition-all duration-500 translate-x-full ${this.getToastClasses(type)}`;
        toast.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4 mt-1">
                    ${this.getToastIcon(type)}
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium leading-relaxed">${message}</p>
                    <p class="text-xs mt-1 opacity-75">${new Date().toLocaleTimeString()}</p>
                </div>
                <button class="ml-4 text-gray-400 hover:text-gray-600 transition-colors" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);

        // Auto-hide
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            toast.style.opacity = '0';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 500);
        }, duration);
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
            'success': '<i class="fas fa-check-circle text-green-500 text-lg"></i>',
            'error': '<i class="fas fa-exclamation-circle text-red-500 text-lg"></i>',
            'warning': '<i class="fas fa-exclamation-triangle text-yellow-500 text-lg"></i>',
            'info': '<i class="fas fa-info-circle text-blue-500 text-lg"></i>'
        };
        return icons[type] || icons.info;
    }

    showStatusUpdate(orderData) {
        const statusDetails = this.getStatusDetails(orderData.status);

        this.showToast(
            `Order ${orderData.order_number}: ${statusDetails.message}`,
            statusDetails.type,
            10000 // Show for 10 seconds
        );
    }

    getStatusDetails(status) {
        const details = {
            'PENDING': {
                message: 'Awaiting payment confirmation',
                type: 'warning',
                icon: 'fas fa-clock',
                color: 'text-yellow-600'
            },
            'PAID': {
                message: 'Payment confirmed, processing order',
                type: 'success',
                icon: 'fas fa-check-circle',
                color: 'text-green-600'
            },
            'PROCESSED': {
                message: 'Order processed and being prepared',
                type: 'info',
                icon: 'fas fa-cog',
                color: 'text-blue-600'
            },
            'ON_DELIVERY': {
                message: 'Out for delivery',
                type: 'info',
                icon: 'fas fa-truck',
                color: 'text-purple-600'
            },
            'WAITING_PICKUP': {
                message: 'Ready for pickup',
                type: 'success',
                icon: 'fas fa-store',
                color: 'text-orange-600'
            },
            'DONE': {
                message: 'Order completed',
                type: 'success',
                icon: 'fas fa-check',
                color: 'text-green-600'
            },
            'CANCELLED': {
                message: 'Order cancelled',
                type: 'error',
                icon: 'fas fa-times',
                color: 'text-red-600'
            }
        };

        return details[status] || {
            message: 'Status unknown',
            type: 'info',
            icon: 'fas fa-question',
            color: 'text-gray-600'
        };
    }

    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        this.saveSettings();

        const message = this.soundEnabled
            ? '🔊 Sound notifications enabled'
            : '🔇 Sound notifications disabled';
        this.showToast(message, 'info');

        return this.soundEnabled;
    }

    loadSettings() {
        const saved = localStorage.getItem('customer_realtime_settings');
        if (saved) {
            const settings = JSON.parse(saved);
            this.soundEnabled = settings.soundEnabled !== false;
        }
    }

    saveSettings() {
        const settings = {
            soundEnabled: this.soundEnabled
        };
        localStorage.setItem('customer_realtime_settings', JSON.stringify(settings));
    }

    destroy() {
        this.stopPolling();
    }

    // Public API for integration
    getEstimatedDeliveryTime(orderData) {
        if (orderData.fulfillment_method === 'PICKUP') {
            return 'Available for pickup';
        }

        const statusTimes = {
            'PAID': '2-3 hours',
            'PROCESSED': '1-2 hours',
            'ON_DELIVERY': '15-30 minutes'
        };

        return statusTimes[orderData.status] || 'Processing';
    }

    getProgressPercentage(status) {
        const progressMap = {
            'PENDING': 10,
            'PAID': 25,
            'PROCESSED': 50,
            'ON_DELIVERY': 75,
            'WAITING_PICKUP': 75,
            'DONE': 100,
            'CANCELLED': 0
        };

        return progressMap[status] || 0;
    }
}

// Global instance for customer tracking
window.CustomerRealtime = new CustomerRealtime();