@extends('layouts.app')

@section('title', 'Test Notifications')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Test Notifications</h1>

        <div class="bg-white p-6 rounded-lg shadow space-y-4">
            <button onclick="testNotification('success')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Test Success Notification
            </button>

            <button onclick="testNotification('error')" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                Test Error Notification
            </button>

            <button onclick="testNotification('info')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Test Info Notification
            </button>

            <button onclick="testNotification('warning')" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                Test Warning Notification
            </button>

            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-2">Current Status:</h3>
                <p><strong>Global App Available:</strong> <span id="appStatus"></span></p>
                <p><strong>ShowNotification Available:</strong> <span id="notificationStatus"></span></p>
                <p><strong>Notifications Array Length:</strong> <span id="notificationCount"></span></p>
            </div>
        </div>
    </div>
</div>

<script>
function testNotification(type) {
    console.log('Testing notification:', type);

    // Update status
    updateStatus();

    if (window.app && window.app.showNotification) {
        const messages = {
            success: 'This is a test success notification!',
            error: 'This is a test error notification!',
            info: 'This is a test info notification!',
            warning: 'This is a test warning notification!'
        };

        window.app.showNotification(messages[type] || 'Test notification', type);
        console.log('Notification sent:', type);
    } else {
        console.error('showNotification not available');
        alert('showNotification not available');
    }

    // Update count after notification
    setTimeout(updateStatus, 100);
}

function updateStatus() {
    document.getElementById('appStatus').textContent = window.app ? 'Available' : 'Not Available';
    document.getElementById('notificationStatus').textContent = (window.app && window.app.showNotification) ? 'Available' : 'Not Available';
    document.getElementById('notificationCount').textContent = (window.app && window.app.notifications) ? window.app.notifications.length : 'N/A';
}

// Update status on page load
document.addEventListener('DOMContentLoaded', updateStatus);
</script>
@endsection