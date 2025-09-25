<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing New Order Status Flow ===\n";

try {
    // Test OrderStatusService dengan flow baru
    $orderStatusService = new App\Services\OrderStatusService();

    echo "\n1. Testing PICKUP Flow:\n";
    echo "   PAID -> PROCESSED -> WAITING_PICKUP -> PICKED_UP -> DONE\n\n";

    // Simulate a pickup order
    $pickupOrder = new App\Models\Order([
        'order_number' => 'TEST-PICKUP',
        'fulfillment_method' => 'PICKUP',
        'status' => 'PAID'
    ]);

    $statuses = ['PAID', 'PROCESSED', 'WAITING_PICKUP', 'PICKED_UP', 'DONE'];

    foreach ($statuses as $status) {
        $pickupOrder->status = $status;
        $available = $orderStatusService->getAvailableStatuses($pickupOrder);
        $statusInfo = $orderStatusService->getStatusInfo($status, 'PICKUP');
        $adminAction = $orderStatusService->getAdminAction($pickupOrder);

        echo "   Status: {$status}\n";
        echo "     Label: {$statusInfo['label']}\n";
        echo "     Available Next: " . implode(', ', $available) . "\n";
        if ($adminAction) {
            echo "     Admin Action: {$adminAction['label']} -> {$adminAction['next_status']}\n";
        }
        echo "\n";
    }

    echo "2. Testing DELIVERY Flow:\n";
    echo "   PAID -> PROCESSED -> ON_DELIVERY -> DONE\n\n";

    // Simulate a delivery order
    $deliveryOrder = new App\Models\Order([
        'order_number' => 'TEST-DELIVERY',
        'fulfillment_method' => 'DELIVERY',
        'status' => 'PAID'
    ]);

    $deliveryStatuses = ['PAID', 'PROCESSED', 'ON_DELIVERY', 'DONE'];

    foreach ($deliveryStatuses as $status) {
        $deliveryOrder->status = $status;
        $available = $orderStatusService->getAvailableStatuses($deliveryOrder);
        $statusInfo = $orderStatusService->getStatusInfo($status, 'DELIVERY');
        $adminAction = $orderStatusService->getAdminAction($deliveryOrder);

        echo "   Status: {$status}\n";
        echo "     Label: {$statusInfo['label']}\n";
        echo "     Available Next: " . implode(', ', $available) . "\n";
        if ($adminAction) {
            echo "     Admin Action: {$adminAction['label']} -> {$adminAction['next_status']}\n";
        }
        echo "\n";
    }

    echo "3. Testing Real Order Update:\n";

    // Get a real order to test
    $realOrder = App\Models\Order::where('status', 'PROCESSED')
        ->where('fulfillment_method', 'PICKUP')
        ->first();

    if ($realOrder) {
        echo "   Found order: {$realOrder->order_number}\n";
        echo "   Current status: {$realOrder->status}\n";
        echo "   Fulfillment: {$realOrder->fulfillment_method}\n";

        $available = $orderStatusService->getAvailableStatuses($realOrder);
        echo "   Available transitions: " . implode(', ', $available) . "\n";

        $adminAction = $orderStatusService->getAdminAction($realOrder);
        if ($adminAction) {
            echo "   Suggested action: {$adminAction['label']}\n";
        }

    } else {
        echo "   No PROCESSED PICKUP orders found\n";
    }

    echo "\n4. Testing Broadcasting Configuration:\n";
    echo "   BROADCAST_CONNECTION: " . config('broadcasting.default') . "\n";
    echo "   Pusher Config:\n";
    echo "     - App Key: " . config('broadcasting.connections.pusher.key') . "\n";
    echo "     - Host: " . config('broadcasting.connections.pusher.options.host') . "\n";
    echo "     - Port: " . config('broadcasting.connections.pusher.options.port') . "\n";
    echo "     - Scheme: " . config('broadcasting.connections.pusher.options.scheme') . "\n";

    echo "\n✅ Order flow testing completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}