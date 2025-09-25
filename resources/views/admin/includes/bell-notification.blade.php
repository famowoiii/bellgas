<!-- Bell Notification Component -->
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open; clearBellNotifications()"
            class="relative p-2 text-gray-600 hover:text-blue-600 transition">
        <i class="fas fa-bell text-xl" :class="bellCount > 0 ? 'text-red-500 animate-pulse' : ''"></i>
        <span x-show="bellCount > 0" x-text="bellCount"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"></span>
    </button>

    <!-- Bell Dropdown -->
    <div x-show="open" @click.away="open = false"
         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border py-2 z-50 max-h-96 overflow-y-auto">
        <div class="px-4 py-2 border-b">
            <h4 class="font-semibold text-gray-800">New Order Notifications</h4>
        </div>

        <div x-show="bellNotifications.length === 0" class="p-4 text-center text-gray-500">
            <i class="fas fa-bell-slash text-2xl mb-2"></i>
            <p>No new notifications</p>
        </div>

        <template x-for="notification in bellNotifications" :key="notification.id">
            <div class="px-4 py-3 border-b hover:bg-gray-50">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-green-600 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800" x-text="notification.message"></p>
                        <p class="text-xs text-gray-500" x-text="notification.time"></p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<!-- Audio element for notification sound -->
<audio id="notificationSound" preload="auto">
    <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+D2xGUgBzuD1fPTeigHKmrE7+GCNQCYH0Sf2+vfcS0AOXDt/W7T8ALm1bvP4WYHKonY6+9DPwGKmXKaY5LMBX4eIGNB1kfg8xvyN0N3OaNXoV2+dFQG1aVG3Kd9VlZAy7dF6dtdJKJl0xJXIa9ysW5bfcZnwqNyOUZDvMfrbNJlkZlbdNZ/FDU8g/vQxlJMrnpTdJNb7BqsQRYo/e9xzZZSy4E5fK13aGk3o5W9sXtP7GvQj29DFKcQF1DXYoVHwbDJPGxPGOpcH2j4D+Xaql0gF2JqWYs53KFTj5qDh/M4KJt2fz6uS8vPq9E3QMqGdNMQ5GBiHUIhk1O5HKJ7YXhGm9c8QLNwp1K6L9Jp3Z8dImfyVAjOLt0VDpZB3kdGHu0Oj9MV7YN8QFQJ1rBNtj1BSWt4Jq1Fkx8PDFcAJhYJuT3jCxUJKo0ZrBqWHd8jCBUO0lR7iVUaGZMQnTD2DktApjITdD+1HG0IrkdnJGOqRZYhUfmhGg7PXjVEQENHs7VGo5TfBsKLVZNI9U0e3CJqBEYT6tU4QTsT1YsKN/I=" type="audio/wav">
</audio>

<script>
// Function to play notification sound
function playNotificationSound() {
    try {
        const audio = document.getElementById('notificationSound');
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(e => console.log('Audio play failed:', e));
        }
    } catch (error) {
        console.log('Audio not supported:', error);
    }
}
</script>