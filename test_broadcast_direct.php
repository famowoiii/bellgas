<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\Log;

echo "🧪 Testing Direct Broadcast to Reverb\n\n";

// Enable logging
\Illuminate\Support\Facades\Config::set('logging.channels.single.level', 'debug');

echo "📋 Broadcasting Configuration:\n";
echo "  - Driver: " . config('broadcasting.default') . "\n";
echo "  - Reverb Host: " . config('broadcasting.connections.reverb.options.host') . "\n";
echo "  - Reverb Port: " . config('broadcasting.connections.reverb.options.port') . "\n";
echo "  - App ID: " . config('broadcasting.connections.reverb.app_id') . "\n";
echo "  - Key: " . config('broadcasting.connections.reverb.key') . "\n\n";

// Get test order
$order = Order::with(['user', 'items.productVariant.product', 'address'])
    ->where('status', 'PAID')
    ->first();

if (!$order) {
    echo "❌ No PAID orders found\n";
    exit(1);
}

echo "✅ Order: {$order->order_number}\n";
echo "💰 Total: \${$order->total_aud}\n\n";

// Test 1: Try broadcasting via Pusher API directly
echo "🚀 Test 1: Broadcasting via Laravel broadcast() helper...\n";

try {
    $data = [
        'order' => [
            'order_number' => $order->order_number,
            'total_aud' => $order->total_aud,
        ],
        'notification' => [
            'message' => 'Test notification'
        ]
    ];

    // Broadcast to public channel
    \Illuminate\Support\Facades\Broadcast::channel('admin-notifications')
        ->broadcast('new-paid-order', $data);

    echo "✅ Broadcast sent via Broadcast facade\n\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n\n";
}

// Test 2: Try using Pusher directly
echo "🚀 Test 2: Broadcasting via Pusher PHP SDK directly...\n";

try {
    $pusher = new \Pusher\Pusher(
        config('broadcasting.connections.reverb.key'),
        config('broadcasting.connections.reverb.secret'),
        config('broadcasting.connections.reverb.app_id'),
        [
            'host' => config('broadcasting.connections.reverb.options.host'),
            'port' => config('broadcasting.connections.reverb.options.port'),
            'scheme' => config('broadcasting.connections.reverb.options.scheme'),
            'useTLS' => false,
        ]
    );

    $data = [
        'order' => [
            'order_number' => $order->order_number,
            'total_aud' => $order->total_aud,
        ],
        'notification' => [
            'message' => 'Direct Pusher test'
        ]
    ];

    $pusher->trigger('admin-notifications', 'new-paid-order', $data);

    echo "✅ Pusher->trigger() called successfully\n";
    echo "📡 Check Reverb terminal now!\n";
    echo "🌐 Check admin browser console!\n\n";

} catch (\Exception $e) {
    echo "❌ Pusher Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n\n";
}

echo "\n✅ Tests complete!\n";
echo "\nIf Reverb terminal shows broadcast activity, the problem is in Laravel Event system.\n";
echo "If Reverb terminal shows NOTHING, the problem is in Pusher/Reverb connection.\n";
