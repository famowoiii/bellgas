<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test broadcasting
use App\Models\Order;
use App\Events\OrderStatusUpdated;

echo "🧪 Testing Broadcast...\n";

// Get a test order
$order = Order::with('user')->first();

if (!$order) {
    echo "❌ No orders found in database\n";
    exit(1);
}

echo "✅ Found order: {$order->order_number}\n";
echo "👤 Customer: {$order->user->first_name} {$order->user->last_name} (ID: {$order->user_id})\n";
echo "📊 Current status: {$order->status}\n";

// Fire the event
echo "\n🚀 Broadcasting OrderStatusUpdated event...\n";

try {
    event(new OrderStatusUpdated($order, $order->status, 'PROCESSING'));
    echo "✅ Event fired successfully!\n";
    echo "📡 Check Reverb terminal for broadcast confirmation\n";
    echo "🌐 Check browser console at http://localhost:8000\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Test complete!\n";
