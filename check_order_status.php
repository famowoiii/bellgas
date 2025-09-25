<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Checking Order Status Issues ===\n";

// Check orders with PROCESSED status and PICKUP method
echo "1. Orders with PROCESSED status and PICKUP method:\n";
$processedPickupOrders = DB::table('orders')
    ->where('status', 'PROCESSED')
    ->where('fulfillment_method', 'PICKUP')
    ->get(['id', 'order_number', 'status', 'fulfillment_method']);

foreach($processedPickupOrders as $order) {
    echo "   - {$order->order_number} (ID: {$order->id})\n";
}
echo "   Total: " . count($processedPickupOrders) . " orders\n\n";

// Check all possible order statuses
echo "2. All order statuses in database:\n";
$allStatuses = DB::table('orders')
    ->select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

foreach($allStatuses as $status) {
    echo "   - {$status->status}: {$status->count} orders\n";
}

// Check fulfillment methods
echo "\n3. Fulfillment methods:\n";
$methods = DB::table('orders')
    ->select('fulfillment_method', DB::raw('count(*) as count'))
    ->groupBy('fulfillment_method')
    ->get();

foreach($methods as $method) {
    echo "   - {$method->fulfillment_method}: {$method->count} orders\n";
}

// Test OrderStatusService
echo "\n4. Testing OrderStatusService for PROCESSED + PICKUP:\n";
$orderStatusService = new App\Services\OrderStatusService();

// Get a sample PROCESSED PICKUP order
$sampleOrder = DB::table('orders')
    ->where('status', 'PROCESSED')
    ->where('fulfillment_method', 'PICKUP')
    ->first();

if ($sampleOrder) {
    $order = App\Models\Order::find($sampleOrder->id);
    $availableStatuses = $orderStatusService->getAvailableStatuses($order);
    $adminAction = $orderStatusService->getAdminAction($order);

    echo "   Sample Order: {$order->order_number}\n";
    echo "   Current Status: {$order->status}\n";
    echo "   Fulfillment Method: {$order->fulfillment_method}\n";
    echo "   Available Next Statuses: " . implode(', ', $availableStatuses) . "\n";
    echo "   Admin Action: " . json_encode($adminAction) . "\n";
} else {
    echo "   No PROCESSED PICKUP orders found for testing\n";
}

echo "\n5. Expected Status Flow:\n";
echo "   PENDING -> PAID -> PROCESSED -> WAITING_PICKUP -> DONE\n";
echo "   (For PICKUP orders)\n";

echo "\nIssue Analysis:\n";
echo "- Current flow: PROCESSED -> WAITING_PICKUP -> DONE\n";
echo "- User expects: PROCESSED -> PICKED_UP (which should be DONE)\n";
echo "- Solution: Use WAITING_PICKUP then DONE, or add PICKED_UP status\n";