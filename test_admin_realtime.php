<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Events\NewPaidOrderEvent;

echo "🧪 Testing Admin Real-time Notification\n\n";

// Get latest PAID order
$order = Order::with(['user', 'items.productVariant.product', 'address'])
    ->where('status', 'PAID')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$order) {
    echo "❌ No PAID orders found\n";
    exit(1);
}

echo "✅ Found PAID order: {$order->order_number}\n";
echo "👤 Customer: {$order->customer_name}\n";
echo "💰 Total: \${$order->total_aud}\n";
echo "⏰ Created: {$order->created_at}\n\n";

echo "🚀 Broadcasting to admin...\n";
echo "📡 Channel: private-admin-notifications\n";
echo "🎯 Event: .new-paid-order\n";
echo "🔑 Authorization required: user.role in ['ADMIN', 'MERCHANT']\n\n";

// Set log level to info temporarily
\Illuminate\Support\Facades\Config::set('logging.channels.single.level', 'info');

try {
    // Broadcast the event
    $event = new NewPaidOrderEvent($order);
    broadcast($event)->toOthers();

    echo "✅ Event broadcast successfully!\n\n";

    // Show what was broadcast
    $data = $event->broadcastWith();
    echo "📦 Broadcast Data:\n";
    echo "   - Order: {$data['order']['order_number']}\n";
    echo "   - Total: \${$data['order']['total_aud']}\n";
    echo "   - Customer: {$data['order']['customer_name']}\n";
    echo "   - Items: {$data['order']['items_count']} items\n";
    echo "   - Message: {$data['notification']['message']}\n\n";

    echo "✅ NEXT STEPS:\n";
    echo "1. Make sure admin is logged in at http://localhost:8000/admin/orders\n";
    echo "2. Check browser console (F12) for message: '💰 New paid order received'\n";
    echo "3. Check Reverb terminal for broadcast activity\n";
    echo "4. Look for toast notification on screen\n";
    echo "5. Check bell icon for red badge\n\n";

    echo "🔍 DEBUGGING:\n";
    echo "If notification doesn't appear, check:\n";
    echo "  - Browser console shows: '✅ Admin subscribed to new paid order notifications'\n";
    echo "  - Browser console shows: '✅ WebSocket connected successfully'\n";
    echo "  - Reverb terminal shows connection from admin browser\n";
    echo "  - Admin user role is ADMIN or MERCHANT\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n✅ Test complete!\n";
