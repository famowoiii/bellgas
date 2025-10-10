<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test NewPaidOrderEvent broadcast
use App\Models\Order;
use App\Events\NewPaidOrderEvent;

echo "🧪 Testing New Paid Order Notification...\n\n";

// Get a test order with PAID status
$order = Order::with(['user', 'items.productVariant.product', 'address'])
    ->where('status', 'PAID')
    ->first();

if (!$order) {
    // If no PAID order, get any order and temporarily set to PAID
    $order = Order::with(['user', 'items.productVariant.product', 'address'])->first();
    if (!$order) {
        echo "❌ No orders found in database\n";
        exit(1);
    }
    echo "⚠️  No PAID orders found, using test order: {$order->order_number}\n";
} else {
    echo "✅ Found PAID order: {$order->order_number}\n";
}

echo "👤 Customer: {$order->customer_name}\n";
echo "💰 Total: \${$order->total_aud}\n";
echo "📊 Status: {$order->status}\n";
echo "📦 Items: {$order->items->count()}\n";

// Fire the NewPaidOrderEvent
echo "\n🚀 Broadcasting NewPaidOrderEvent to admin...\n";
echo "📡 Channel: private-admin-notifications\n";
echo "🎯 Event: .new-paid-order\n\n";

try {
    broadcast(new NewPaidOrderEvent($order))->toOthers();
    echo "✅ Event broadcast successfully!\n\n";

    echo "Expected behavior:\n";
    echo "  1. Admin browser should show toast: 'Order {$order->order_number} has been PAID!'\n";
    echo "  2. Bell icon should turn red with notification badge\n";
    echo "  3. Sound notification should play\n";
    echo "  4. Console log: '💰 New paid order received'\n";
    echo "  5. If on /admin/orders page, should auto-reload after 2 seconds\n\n";

    echo "📡 Check Reverb terminal for broadcast confirmation\n";
    echo "🌐 Check admin browser console at http://localhost:8000/admin/orders\n";
    echo "🔔 Check bell icon in admin navbar\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Test complete!\n";
